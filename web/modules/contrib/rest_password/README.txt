Please see the project page for more information.
at https://www.drupal.org/project/rest_password


Lost password
-------------

Method: POST
ENDPOINT: SITE + /user/lost-password?_format=json

{
  "mail": "your@yoursite.email"
}


Reset lost password via temp password
-------------------------------------

Method: POST
ENDPOINT: SITE + /user/lost-password-reset?_format=json

{
  "name": "DRUPALUSERNAME",
  "temp_pass":"TEMP_PASSWORD_SENT_IN_EMAIL"
  "new_pass":"NEW_PASS_WORD"
}
