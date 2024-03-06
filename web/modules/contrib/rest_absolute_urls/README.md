# REST Absolute URLs

Retrieve an entire menu tree in a single endpoint.

## Introduction

REST Absolute URLs is a tiny module which replaces relative file / image URLs
with absolute URLs in the content fields when they are exposed to any
headless Drupal successor (like JSON:API or any other).

## Installation

Install as you would normally install a contributed Drupal module.

## Usage example

You add an image to a body field using Ckeditor: you can see this image
in Drupal, but when you fetch this node with any frontend framework
(like React.js, Angular, etc), then this image is not
visible. That's because the image's URL is relative, and can't be found.

This module automatically replaces such relative internal URLs with absolute
URLs, so they point to the right server with Drupal where the files are really
located.

## Requirements

* This module uses the core Serialization module.

## Suggestions

* You can use the core JSON:API module to create a webservice.
