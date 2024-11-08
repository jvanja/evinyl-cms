<?php

namespace Drupal\queue_ui;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Queue\DelayableQueueInterface;
use Drupal\Core\Queue\DelayedRequeueException;
use Drupal\Core\Queue\QueueWorkerManagerInterface;
use Drupal\Core\Queue\RequeueException;
use Drupal\Core\Queue\SuspendQueueException;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Error;

/**
 * Batch controller to process a queue.
 *
 * Class QueueUIBatch declaration.
 *
 * @package Drupal\queue_ui
 */
class QueueUIBatch implements QueueUIBatchInterface {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * Constructor of the Queue UI Batch service.
   *
   * @param \Drupal\Core\Queue\QueueWorkerManagerInterface $queueManager
   *   Queue manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger.
   * @param mixed|\Drupal\Core\Queue\QueueFactory $queueFactory
   *   Queue factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   */
  public function __construct(
    protected QueueWorkerManagerInterface $queueManager,
    protected ModuleHandlerInterface $moduleHandler,
    protected MessengerInterface $messenger,
    protected mixed $queueFactory,
    protected ?LoggerChannelFactoryInterface $logger = NULL,
  ) {
    if (is_null($logger)) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $logger argument is deprecated in queue_ui:3.2.0 and will be required in queue_ui:4.0.0. See https://www.drupal.org/node/3482168', E_USER_DEPRECATED);

      // @phpstan-ignore-next-line
      $this->logger = \Drupal::service('logger.factory');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function batch(array $queues): void {
    $batch = (new BatchBuilder())
      ->setTitle($this->t('Processing queues'))
      ->setFinishCallback([$this, 'finish']);
    foreach ($queues as $queue_name) {
      $batch->addOperation([$this, 'step'], [$queue_name]);
    }
    batch_set($batch->toArray());
  }

  /**
   * {@inheritdoc}
   */
  public function step(string $queue_name, array &$context): void {
    // Make sure every queue exists. There is no harm in trying to recreate
    // an existing queue.
    $info = $this->queueManager->getDefinition($queue_name);
    $this->queueFactory->get($queue_name)->createQueue();
    $queue_worker = $this->queueManager->createInstance($queue_name);
    $queue = $this->queueFactory->get($queue_name);

    $num_of_items = $queue->numberOfItems();
    if (!array_key_exists('num_of_total_items', $context['sandbox'])
      || $context['sandbox']['num_of_total_items'] < $num_of_items
    ) {
      $context['sandbox']['num_of_total_items'] = $num_of_items;
    }

    $context['finished'] = 0;
    $context['results']['queueName'] = $info['title'];

    $title = $this->t('Processing queue %name: %count items remaining', [
      '%name' => $info['title'],
      '%count' => $num_of_items,
    ]);

    try {
      if ($item = $queue->claimItem()) {
        // Let other modules alter the title of the item being processed.
        $this->moduleHandler
          ->alter('queue_ui_batch_title', $title, $item->data);
        $context['message'] = $title;

        // Process and delete item.
        $queue_worker->processItem($item->data);
        $queue->deleteItem($item);

        $num_of_items = $queue->numberOfItems();

        // Update context.
        // @todo Figure out the proper way to read the queue item ID.
        $context['results']['processed'][] = $item->item_id ?? $item->qid ?? NULL;
        $context['finished'] = ($context['sandbox']['num_of_total_items'] - $num_of_items) / $context['sandbox']['num_of_total_items'];
      }
      else {
        // If we cannot claim an item we must be done processing this queue.
        $context['finished'] = 1;
      }
    }
    catch (DelayedRequeueException $e) {
      // The worker requested to delay the item,
      // see Drupal\Core\Cron for details.
      if (isset($item) && $queue instanceof DelayableQueueInterface) {
        $queue->delayItem($item, $e->getDelay());
      }
    }
    catch (RequeueException $e) {
      if (isset($item)) {
        // The worker requested the task be immediately requeued.
        $queue->releaseItem($item);
      }
    }
    catch (SuspendQueueException $e) {
      // If the worker indicates there is a problem with the whole queue.
      if (isset($item)) {
        // Release the item and skip to the next queue.
        $queue->releaseItem($item);
      }

      Error::logException($this->logger->get('queue_ui'), $e);
      $context['results']['errors'][] = $e->getMessage();

      // Marking the batch job as finished will stop further processing.
      $context['finished'] = 1;
    }
    catch (\Exception $e) {
      // In case of any other kind of exception, log it and leave the item
      // in the queue to be processed again later.
      Error::logException($this->logger->get('queue_ui'), $e);
      $context['results']['errors'][] = $e->getMessage();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function finish(bool $success, array $results, array $operations): void {
    // Display success of no results.
    if (!empty($results['processed'])) {
      $this->messenger->addMessage(
        $this->formatPlural(
          count($results['processed']),
          'Queue %queue: One item successfully processed.',
          'Queue %queue: @count items successfully processed.',
          ['%queue' => $results['queueName']]
        )
      );
    }
    elseif (!isset($results['processed'])) {
      $this->messenger->addWarning($this->t(
        "Items were not processed. Try to release existing items or add new items to the queues."
      ));
    }

    // Display errors.
    if (!empty($results['errors'])) {
      $this->messenger->addError(
        $this->formatPlural(
          count($results['errors']),
          'Queue %queue error: @errors',
          'Queue %queue errors: <ul><li>@errors</li></ul>',
          [
            '%queue' => $results['queueName'],
            // We only want list markup for the plural case.
            // Thus is it very appropriate that implode
            // will not add glue for single entry array.
            '@errors' => Markup::create(implode('</li><li>', $results['errors'])),
          ]
        )
      );
    }
  }

}
