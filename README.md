Track how many times in the last x seconds z ip address has submitted a form. Redis is used for quick IO and no dependence on pinging the drupal database.

Redis holds the last 5 form submits by ip address. If there have been 5 form submissions in 10 seconds (this is editable in the admin area) then we call cloudflare to register the ip address as spam and a Drupal log message is created.

The redis data is automatically purged after 14 days to keep memory low. We keep them for two weeks so that later we can build a viewer and see what or who is hitting our forms.

TO INSTALL
- Enable the module
- Change the form_id to the form you need to track on the Drupal site. In this case the search feature I believe.
- Update the settings with the Cloudflare api key, email address, and zone.

I have never used this feature on cloudflare so my api call may not be the right one.
