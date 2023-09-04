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
        // - TODO:  <09/04/2023, vanja> -
        // Append a real button somewhere on the page
        const addLyricsButton = document.querySelector(
          "#field-a-side-songs-values h4"
        );
        addLyricsButton.addEventListener("click", () => {
          getLyricsFromMusicMatch(elem);
        });
      }

      function getLyricsFromMusicMatch(elem) {
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
        const songsNamesFields = elem.querySelectorAll(
          ".paragraph--type--a-side-songs"
        );

        // search the musicmatch API for the songs
        if (artistName.length > 1) {
          songsNamesFields.forEach(async (field) => {
            const songIdArr = field.classList.value
              .split(" ")
              .filter((cl) => cl.indexOf("paragraph-id--") == 0);
            const songId = songIdArr[0].replace("paragraph-id--", "");

            const songName = field.querySelector(
              ".field--name-field-song-name"
            ).innerText;
            const queryParams = `q_artist=${encodeURIComponent(
              artistName
            )}&q_track=${encodeURIComponent(songName)}`;
            const apiEndPoint = apiEndPointBase + queryParams + apiKey;
            const response = await fetch(apiEndPoint);
            const lyrics = await response.json();

            // update each tracks with new lyrics
            if (lyrics.message.header.status_code === 200) {
              updateTrackWithLyrics(
                songId,
                lyrics.message.body.lyrics.lyrics_body
              );

              // add the musicmatch tracker script
              const trackingScript =
                lyrics.message.body.lyrics.script_tracking_url;
              addMusicMatchTrackerScript(trackingScript);

              // TODO: add lyrics copyright message
              const copyrightMessage =
                lyrics.message.body.lyrics.lyrics_copyright;
            }
          });
        }
      }

      async function updateTrackWithLyrics(id, lyricsResponse) {
        const lyrics =
          "<p>" + lyricsResponse.replaceAll("\n", "<br/>") + "</p>";

        const lyricsApi =
          window.drupalSettings.path.baseUrl + "admin/api/lyrics";
        const updateTracksResponse = await fetch(lyricsApi, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            id: id,
            lyrics: lyrics,
          }),
        });
        const responseJson = await updateTracksResponse.json();
        console.log(responseJson);
      }

      function addMusicMatchTrackerScript(script) {
        console.log("adding tracking:", script);
      }
    },
  };
})(Drupal);
