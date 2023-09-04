/**
 * @file
 * Custom JS for evinyl_lyrics module
 */
(function (Drupal) {
  "use strict";

  Drupal.behaviors.evinylLyricsJS = {
    attach: function (context) {
      if (
        context.classList &&
        context.classList.contains("page-node-type-album")
      ) {
        once("getLyricsBehariour", "html").forEach(function (element) {
          processAlbumHtml(element);
        });
      }
      function processAlbumHtml(elem) {
        // get song names
        const apiKey = "&apikey=d778b574003da2f491a96371018c912a";
        const artistName = elem
          .querySelector(".field--name-field-artist-term input")
          .value.replace(/[(\d*)]/g, "")
          .trim();

        // route API calls through proxy from localhost to avoid CORS errors
        const apiEndPointBase =
          window.location.host === "localhost"
            ? "http://localhost:8010/proxy/ws/1.1/matcher.lyrics.get?"
            : "https://api.musixmatch.com/ws/1.1/matcher.lyrics.get?";
        const songsLyrics = new Map();
        const songsNamesFields = elem.querySelectorAll(
          ".field--name-field-song-name"
        );

        // search the musicmatch API for the songs
        if (artistName.length > 1) {
          songsNamesFields.forEach(async (field) => {
            const songName = field.innerText;
            const queryParams = `q_artist=${encodeURIComponent(
              artistName
            )}&q_track=${encodeURIComponent(songName)}`;
            const apiEndPoint = apiEndPointBase + queryParams + apiKey;
            // console.log(apiEndPoint);
            const response = await fetch(apiEndPoint);
            const lyrics = await response.json();
            // console.log(lyrics);
            songsLyrics.set(
              field.innerText,
              lyrics.message.body.lyrics.lyrics_body
            );
            // update the track with the lyrics from the musicmatch API
          });
        }
      }
    },
  };
})(Drupal);
