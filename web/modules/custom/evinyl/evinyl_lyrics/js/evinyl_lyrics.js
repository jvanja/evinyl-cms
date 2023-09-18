/**
 * @file
 * Custom JS for evinyl_lyrics module
 */
;(function (Drupal) {
  'use strict'

  Drupal.behaviors.evinylLyricsJS = {
    attach: function (context) {
      if (context.classList && context.classList.contains('page-node-type-album')) {
        once('getLyricsBehariour', 'html').forEach(function (element) {
          const isAdmin = context.classList.contains('role_administrator')
          if (isAdmin) {
            processAlbumHtml(element)
          }
        })
      }
      function processAlbumHtml(elem) {
        let button = document.createElement('button')
        button.setAttribute('id', 'add-lyrics')
        button.setAttribute('class', 'button button--primary')
        button.setAttribute('style', 'margin-bottom: 20px;')
        button.innerHTML = 'Add lyrics from MusicMatch'
        elem.querySelector('#tracks #field-a-side-songs-add-more-wrapper').prepend(button)

        const addLyricsButton = elem.querySelector('#add-lyrics')
        addLyricsButton.addEventListener('click', (e) => {
          e.preventDefault()
          getLyricsFromMusicMatch(elem)
        })
      }

      function getLyricsFromMusicMatch(elem) {
        // get song names
        const apiKey = '&apikey=d778b574003da2f491a96371018c912a'
        const artistName = elem
          .querySelector('.field--name-field-artist-term input')
          .value.replace(/[(\d*)]/g, '')
          .trim()

        // route API calls through proxy from localhost to avoid CORS errors
        // const apiEndPointBase =
        //   window.location.host === 'localhost'
        //     ? 'http://localhost:8010/proxy/ws/1.1/matcher.lyrics.get?'
        //     : 'https://api.musixmatch.com/ws/1.1/matcher.lyrics.get?'
        const songsNamesFields = elem.querySelectorAll('.paragraph--type--a-side-songs')

        // search the musicmatch API for the songs
        if (artistName.length > 1) {
          songsNamesFields.forEach(async (song_paragraph) => {
            // add the AJAX laoder animation
            addStatusToTrackElement(song_paragraph.querySelector('.layout__region--second'), 'loading')

            const songIdArr = song_paragraph.classList.value
              .split(' ')
              .filter((cl) => cl.indexOf('paragraph-id--') == 0)
            const songId = songIdArr[0].replace('paragraph-id--', '')
            const songName = song_paragraph.querySelector('.field--name-field-song-name').innerText
            // const queryParams = `q_artist=${encodeURIComponent(artistName)}&q_track=${encodeURIComponent(songName)}`
            // const apiEndPoint = apiEndPointBase + queryParams + apiKey

            // call the Drupal module and point
            try {
              const lyricsApi = window.drupalSettings.path.baseUrl + 'admin/api/lyrics'
              const updateTracksResponse = await fetch(lyricsApi, {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                  id: songId,
                  artistName,
                  songName,
                }),
              })
              const responseJson = await updateTracksResponse.json()
              if (responseJson.status === 200) {
                addStatusToTrackElement(song_paragraph.querySelector('.layout__region--second'), 'success')
              } else {
                addStatusToTrackElement(song_paragraph.querySelector('.layout__region--second'), 'error')
              }
            } catch (error) {
              addStatusToTrackElement(song_paragraph.querySelector('.layout__region--second'), 'error')
            }
            // call the musicmatch API
            // try {
            //   const response = await fetch(apiEndPoint)
            //   const lyrics = await response.json()
            //   if (lyrics.message.header.status_code === 200) {
            //     // update the CMS with the new lyrics
            //     updateTrackWithLyrics(songId, lyrics.message.body.lyrics.lyrics_body, song_paragraph)
            //
            //     // add the musicmatch tracker script
            //     const trackingScript = lyrics.message.body.lyrics.script_tracking_url
            //     addMusicMatchTrackerScript(trackingScript)
            //
            //     // TODO: add lyrics copyright message
            //     const copyrightMessage = lyrics.message.body.lyrics.lyrics_copyright
            //   } else {
            //     addStatusToTrackElement(song_paragraph.querySelector('.layout__region--second'), 'error')
            //   }
            // } catch (error) {
            //   addStatusToTrackElement(song_paragraph.querySelector('.layout__region--second'), 'error')
            // }

            // update each tracks with new lyrics
          })
        }
      }

      // async function updateTrackWithLyrics(id, lyricsResponse, song_paragraph) {
      //   const lyrics = '<p>' + lyricsResponse.replaceAll('\n', '<br/>') + '</p>'
      //
      //   const lyricsApi = window.drupalSettings.path.baseUrl + 'admin/api/lyrics'
      //   const updateTracksResponse = await fetch(lyricsApi, {
      //     method: 'POST',
      //     headers: {
      //       'Content-Type': 'application/json',
      //     },
      //     body: JSON.stringify({
      //       id: id,
      //       lyrics: lyrics,
      //     }),
      //   })
      //   const responseJson = await updateTracksResponse.json()
      //   addStatusToTrackElement(song_paragraph.querySelector('.layout__region--second'), 'success')
      //   console.log(responseJson)
      // }

      function addMusicMatchTrackerScript(script) {
        console.log('adding tracking:', script)
      }

      function addStatusToTrackElement(element, status) {
        console.log(status)
        if (element.querySelector('#tracks .lyrics-loader-status'))
          element.querySelector('#tracks .lyrics-loader-status').remove()
        const statusElement = document.createElement('div')
        statusElement.setAttribute('class', 'lyrics-loader-status')
        element.appendChild(statusElement)
        switch (status) {
          case 'loading':
            console.log('loading')
            statusElement.classList.add('loading')
            break
          case 'success':
            statusElement.classList.add('success')
            break
          case 'error':
            statusElement.classList.add('error')
            break
          default:
            break
        }
      }
    },
  }
})(Drupal)
