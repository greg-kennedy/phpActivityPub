# Using this bot with Apache webserver

To aid anyone else who wants to use this simple bot system on a site using Apache, here are the ReWrite rules
that we use.

The files from this code are installed in DOCUMENT_ROOT/activitypub. We want to provide a users link that doesn't
necessarily reveal the install path, and we also need to make sure webfinger can be found under .well-known

(Using these rules is why we also have a change in functions.php from the original, to detect the correct path
when webfinger is called).

Assuming you have RewriteEngine On already in your site config, add these two rules

    # For ActivityPub
    RewriteRule ^/.well-known/webfinger /activitypub/webfinger.php [L,QSA]
    RewriteRule ^/users/(.*) /activitypub/user.php?user=$1 [L]
    
