=== Anti-Splog ===
Contributors: DerN3rd (WMS N@W)
Donate link: https://n3rds.work/docs/anti-splog-handbuch/
Tags: spam, splog, multisite, security
Requires at least: WordPress 4.7
Tested up to: 5.9.0
Stable tag: 2.3.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Anti-Splog erspart jede Menge Arbeit, indem es Spam-Blogs (Splogs) und Spam-Multisite-Registrierungen blockiert und intelligent tötet.


## Anti-Splog erspart jede Menge Arbeit, indem es Spam-Blogs (Splogs) und Spam-Multisite-Registrierungen blockiert und intelligent tötet.

Built originally for Edublogs, we’ve used years of experience and data from millions of blogs to block and kill spam blogs that waste time and cost you money. 


 Anti-splog gathers spam fighting information from millions of blogs.

### Crowdsourced Splog Fighting

The Anti-Splog API uses crowdsourced data from thousands of networks and millions of blogs to find and block spam. So when one of user flags a splog or spam you don’t have to worry about it showing up on your network.

 

## Five Layers of Protection

### Anti-Splog is the ultimate splog killer with five layers of protection.


**Signup Prevention** Limit the number of blog signups per 24 hours per IP.


**Change Signup Page Location** Automatically move your _wp-signup.php_ file every 24 hours. Incredibly effective and user-friendly.


**Blacklist IPs** Stop users from creating sites from any IP address that’s been previously used to create a splog.


**Human Tests** Provides three sign up protection options to choose from: user-defined questions and reCAPTCHA.


 

### Post Monitoring

Anti-Splog continues to fight spam with post monitoring even after sites have been created. The second a spam post is written, no matter how cleverly disguised it is, our API will find it and shut it down for good.
 Give users a quick way to validate their blog.

 


 Provide layers of protection and a simple form for contacting support.

### Fast & Simple Moderation

Anti-splog sorts spam, suspicious, and valid sites into lists so you can quickly manage hundreds of blogs. Instant previews let you make fast informed decisions without ever leaving the page.

 

### Better Customer Care

If a site is flagged as spam, site administrators can easily request a review. Display a simple contact form and reactivation instructions on sites that have been marked as spam.

 Notify users of changes with automated emails.

   


 Quickly preview and validate content.

### Always in Control

Every time a new blog or post is created, we scan it with our secret ever-tweaking logic to make sure your network doesn’t get flooded with ads or malicious content. Spammed blogs stay archived in your dashboard so you can quickly restore them at any time.

**Activate Anti-Splog and stop spam on your network.**

## Anti-Splog will save you days of work by blocking and intelligently killing spam blogs (splogs).

Built originally for Edublogs, we’ve used years of experience and data from millions of blogs to block and kill spam blogs that waste time and cost you money. 


 Anti-splog gathers spam fighting information from millions of blogs.

### Crowdsourced Splog Fighting

The Anti-Splog API uses crowdsourced data from thousands of networks and millions of blogs to find and block spam. So when one of user flags a splog or spam you don’t have to worry about it showing up on your network.

 

## Five Layers of Protection

### Anti-Splog is the ultimate splog killer with five layers of protection.


**Signup Prevention** Limit the number of blog signups per 24 hours per IP.


**Change Signup Page Location** Automatically move your _wp-signup.php_ file every 24 hours. Incredibly effective and user-friendly.


**Blacklist IPs** Stop users from creating sites from any IP address that’s been previously used to create a splog.


**Human Tests** Provides three sign up protection options to choose from: user-defined questions and reCAPTCHA.


 

### Post Monitoring

Anti-Splog continues to fight spam with post monitoring even after sites have been created. The second a spam post is written, no matter how cleverly disguised it is, our API will find it and shut it down for good.

 Give users a quick way to validate their blog.

 


 Provide layers of protection and a simple form for contacting support.

### Fast & Simple Moderation

Anti-splog sorts spam, suspicious, and valid sites into lists so you can quickly manage hundreds of blogs. Instant previews let you make fast informed decisions without ever leaving the page.

 

### Better Customer Care

If a site is flagged as spam, site administrators can easily request a review. Display a simple contact form and reactivation instructions on sites that have been marked as spam.


 Notify users of changes with automated emails.

   


 Quickly preview and validate content.

### Always in Control

Every time a new blog or post is created, we scan it with our secret ever-tweaking logic to make sure your network doesn’t get flooded with ads or malicious content. Spammed blogs stay archived in your dashboard so you can quickly restore them at any time.

**Activate Anti-Splog and stop spam on your network.**

## Usage

**If you are using Multi-DB****:**

*   You need to add the global table lines to db-config.php BEFORE installing Anti-Splog plugin or running the sql.txt
*   Add this line to your db-config.php if using multi-db:

add_global_table('ust');



### To install

For help with installing plugins please see our [Plugin installation guide](https://premium.wpmudev.org/wpmu-manual/installing-regular-plugins-on-wpmu/). Once installed log into to your admin panel, visit **Network Admin -> Plugins** and **Network Activate** the plugin. Please move the blog-suspended.php file from the Anti-Splog plugin to the /wp-content/ directory ([using FTP](https://premium.wpmudev.org/wpmu-manual/introduction-to-ftp-and-using-ftp-clients/)). **Please Note:**

*   In the rare occurrence the auto-install does not add the db table(s) and fill them for you then run the sql code in "sql.txt" on your wpmu db IN ORDER!
*   blog-suspended.php shows the user friendly spammed page with the review form so that users can request their blog to be unspammed



### To Set Up And Configure Anti-Splog

1.  Go to **Network Admin > Settings > Anti-Splog > Settings**

*   Remember whenever you make any changes on this Setting page to click **Save Changes** at the bottom of the page



   5.  Add your API key to your Anti-Splogs Setting page and click **Check Key**

*   If your API key is working properly the API Key field will change to green

 6.  Select your Blog Signup Splog Certainty

*   When a blog is first created the signup info to our premium server where it is rated based on our secret ever-tweaking calculations and logic.
*   Our API return a Blog Signup Splog Certainty
*   Blogs that are greater than or equal to the number you select are automatically spammed and will be listed on your **Recent Splogs** page
*   On Edublogs an 85 % Blog Signup Splog Certainty is used because it is very accurate at spamming splogs with minimal spamming on non-splogs


 7.  Select your Post Splog Certainty

*   When a new post is published it is sent to our premium server where it is rated based on our secret ever-tweaking calculations, keywords and logic.
*   Our API return a Post Splog Certainty
*   Blogs that are greater than or equal to the number you select are automatically spammed and will be listed on your **Recent Splogs** page
*   On Edublogs an 78 % Post Splog Certainty is used because it is very accurate at spamming splogs with minimal spamming on non-splogs


 8.  Select your Limit Blog Signups Per Day

*   This is designed to slow limit the number of splogs that can be registers per day from an IP address as sploggers often register large numbers of blogs in a short time
*   Edublogs uses Unlimited because schools often have lots of blogs being created from the one IP address. **Most sites can set this to a low number like 1 or 2 unless they cater to groups of users using the same internet connection.**


 9.  Choose if you want to Rename wp-signup.php

*   This auto moves the signup URL every 24 hours
*   It's a user friendly method of stopping splog bots in their tracks
*   It is not **BuddyPress Compatible**
*   This option is not used on Edublogs


 10\. Choose if you want to Spam/Unspam Blog Users

*   Some sploggers create several blogs using the same username -- this spams their username prevent them from creating further blogs with that username


 11.  Select your Queue Display preferences

*   These settings determine how blogs and posts are displayed and previewed on your Suspected Blogs, Recent Splogs and Ignored Splogs page
*   Edublogs settings used are shown below


 12.  For now remove all keywords from Spam Keyword Search as it is covered later in "How To Use Anti-splog" 13.  Select your Additional Signup Protection

*   These are designed to prevent automated spam bot signups
*   They are used with caution as you don't want to annoy genuine users who want to sign up on your network
*   Edublogs uses reCaptcha

### **To set up Admin Defined Questions**

i) Select **Admin Defined Questions** from the drop down menu on Additional Signup Protection



ii) Add your Questions and answers



iii) Check it is working properly by creating a new blog using your sign up page



### **To set up reCAPTCHA** **- Advanced Captcha**

i) Select **reCAPTCHA** **- Advanced Captcha** from the drop down menu on Additional Signup Protection



ii) Click on the link to visit the [reCAPTCHA website](http://recaptcha.net/whyrecaptcha.html) to grab your public and private key - you will need to sign up for an account if you don't have one!

iii) Add your public and private key(Edublogs uses a White Theme)


iii) Check it is working properly by creating a new blog using your sign up page


15.  When finished setting up and configuring your Settings page click on **Save Changes**

### **How to Use the Suspected Blogs Page**

The Suspected Blogs page lists any blogs that the plugin thinks may be splogs based on:

1.  Having a Splog Certainty greater than 0 % as returned by our API from our servers
2.  Containing at least 1 keyword in recent posts from the keyword list you added into the **Spam Keyword Search** on the Settings page

The blogs on the Suspected Blogs Page are listed from most suspected blog to the least suspected based on the following order

1.  Number of keyword matches
2.  % splog certainty (as returned by the API) with those with the highest splog certainty being listed first
3.  Last updated

Hovering your mouse over the domain or Blog User brings up the **Spam** and **Ignore** action menu. **The idea is you work through the suspected blog page to:**

1.  Click on **Spam** blogs that are definitely splogs -- this moves them to the **Recent Splog page** where you can unspam them if necessary
2.  Click on **Ignore** blogs that are definitely not splogs -- this moves them to the **Ignored Blog page** where you can spam them if necessary (in case you made a mistake)
3.  **Take no action** because you haven't decided if a blog is/isn't a splog -- this keeps them on the **Suspected Splog page** so you can monitor their activity (when unsure it is best to leave them on the Suspected Blog page)


 **Here is a flowchart to trace the functionality of Anti-Splog:** The Anti-Splog Process Diagram (**Click on Image to Zoom In** for a closer look at each step)


**How To Spam Blogs** Some splogs are obvious from the username, blog URL or post titles they use-- if you are confident that they are a splog you can choose to either: **a) Spam their URL** by clicking on **Spam** action link under their domain

*   this spams that blog and moves it to the **Recent Splogs page**


 b) Or **spam their registered IP** by clicking on **Spam** action link next to their IP address and then clicking _OK_

*   this shows you how many blogs that have been created from the IP address and how many blogs have already been spammed from that IP address
*   sploggers often create large numbers of splogs from the same IP address so this is a fast way to spam a large number of splogs in one go
*   be patient when using the spam action menu next to registered IP as there can be a bit of a delay in checking the numbers of blogs and spammed at an IP


 **Previewing Suspected Splogs** With most blogs on the suspected blog page it won't be obvious that they are splogs so you will need to preview them. As you work through previewing the suspected splogs you need to:

1.  Spam definite splogs by either clicking on **Spam** next to their domain or their IP address
2.  Click on **Ignore** for blogs that definitely aren't splogs
3.  Take no action of blogs you aren't sure about - so you can continue to monitor them on the Suspected Splog page

To check you should start by first clicking on their post title.  This loads a preview of that post.

*   Obvious signs of splogs are lots of link within post content that link to the same or similar websites - have a quick check of all links in their post to see where they are linking as some sploggers are very clever
*   Links to a websites at the bottom of the post
*   If unsure after checking a few posts then make sure you preview the blog because some splogs are clever and put the links in their blogroll on the sidebar


 If the blog doesn't have any post then click on the domain name to load a preview of the blog

*   this loads slower than the previewing a post so in most situations it's best to use post preview where possible
*   some sploggers will replace the Hello World with their spam post and this is the only post they will have on the blog


 Make sure for any suspect blogs you **check their blogroll** as some clever splogs hide their links in the sidebar

*   In the example below the splog linked to sex sites using the blog roll while hiding it in what appeared to be a harmless pet blog.


### Working With The Splog Review Request Form

The blog-suspended.php is used to show the user friendly spammed page with the review form so that users can request their blog to be unspammed. This form is the fastest option for unspamming blogs incorrectly marked as spam. 


 The Splog Review Request Form is submitted to the email address listed in **Network Admin > Settings** -- make sure you have changed the email address or [set up your email account](https://premium.wpmudev.org/wpmu-manual/setting-up-your-support-email-account/). 


 All you need to do is click on the review link in the email: 


 This takes you to the location of that blog on your **Recent Splog** page. Now unspamming a blog is as simple as:

1.  Click on post titles to preview the posts to confirm it isn't a splog
2.  Click on the **Not Spam** action link to unspam a blog


### What To Do If You Incorrectly Spam A Large Number of blogs from the same IP address

You can quickly unspammed blogs from the same IP address as follows: 

1.  Copy the registered IP address of one of the blogs for the Recent splog page 

2.  Go to **Network Admin > Sites** 

3.  Add the IP address and then click Search IP 

4.  Select the blogs and then click **Not Spam**

*   On Edublogs it's easy to spot if blogs have accidentally been marked as spam from the one IP address by checking their email address as they'll often be from the same institutional domain email address


### Working with Spam Keyword searches

The API service works by checking recently created blogs, usernames and newly published posts. Whereas the Keyword search is designed to find old and inactive splogs that the API service would no longer catch -- since the splogs aren't being updated. It works by referencing keywords in posts using our [Post Indexer plugin](https://premium.wpmudev.org/project/post-indexer). To work with the Spam Keyword search:

*   You must have installed our [Post Indexer plugin](https://n3rds.work/piestingtal_source/multisite-beitragsindex-plugin/).
*   You must understand that the keyword search only referenced posts back to the date you originally installed [Post Indexer plugin](https://n3rds.work/piestingtal_source/multisite-beitragsindex-plugin/) -- the Post Indexer doesn't index any post prior to the date it was installed
*   The Keyword should only be added to the Spam Keyword search on the Anti-splog Settings page temporarily while searching for splogs
*   Once you've used a keyword, actioned the suspected splogs located using the keyword on the Suspected splog page then you SHOULD remove that search term from your Anti-splog Settings page
*   Only use about 2 keywords per search as it can slow down or timeout your Suspected Blogs page -- if you get a white page on the Suspected Blogs page just remove the keywords you've added
*   Remember keywords are not case sensitive and can match any part of the word.
*   Ideally try terms being used by your splogs but aren't used much by genuine blogs

**To use the spam keyword search:** 

1.  Go to **Network Admin > Settings > Anti-Splog** 

2.  Click on the **Settings** tab 

3.  Add your Spam Keyword search terms - only add 2 keywords at a time 

4.  Click **Save Changes** at the bottom of your Anti-splog **Settings page** **


 ** 5.  Check the suspected splogs located using the keyword on the Suspected splog page (they will appear at the top of the page) 


 6.  Click on **Spam** blogs that are definitely splogs or on **Ignore** blogs that are definitely not splogs 
 
 7.  Once completed go back to your Settings page and do a new search 
 
 8.  When finished _remove all keywords_ from the Spam Keyword Search field on your **Settings page** and click **Save Changes**

### Creating your own signup page

If you're an advanced user and you wish to create your own wp-signup.php form then you can create one named **custom-wpsignup.php** and drop this into your /wp-content/ folder.

[POWERED BY PSOURCE](https://n3rds.work/psource_kategorien/psource-plugins/)

[Projektseite](https://n3rds.work/piestingtal_source/)
[Handbuch](https://n3rds.work/docs/)
[Supportforum](https://n3rds.work/forums/forum/)
[GitHub](https://github.com/piestingtal-source/)

== Mehr PSOURCE ==

= PS MAPS PLUGIN =

Hole mehr aus PS Events indem Du Deinen Veranstaltungen total anpassbare, interkative Google-Karten spendierst.
So lotst Du Deine Teilnehmer direkt zum Veranstaltungsort!
Mit unserem [PS Maps Plugin](https://n3rds.work/piestingtal_source/ps-google-maps-plugin/) entfaltet PS Events ganz neues Potential.

= PSECOMMERCE PLUGIN =

Verkaufe Online-Tickets oder verechne Teilnahme-Gebüren und verechne es über die PSeCommerce Gateways.
Du kannst Deine Events auch als Shop-Produkte anlegen lassen.
Lerne [PSeCommerce](https://n3rds.work/piestingtal_source/psecommerce-shopsystem/) kennen!

= PS MITGLIEDSCHAFTEN PLUGIN =

Deine Benutzer bezahlen für eine besondere Mitgliedschaft? 
Belohne sie mit Ermäßigungen wenn sie an einer Deiner Veranstaltungen teilnehmen!
Noch nicht? - Dann empfehlen wir Dir, einen Blick auf unser [PS Mitgliedschaften Plugin](https://n3rds.work/piestingtal_source/ps-mitgliedschaften-plugin/) zu werfen.

= PS-ENEWSLETTER PLUGIN =

(Empfohlen für ClassicPress)
[PSeNewsletter](https://n3rds.work/piestingtal_source/psenewsletter-wordpress-newsletter-ohne-drittanbieter/) informiert Deine Event-Teilnehmer über weitere Event-Details, halte Deine Besucher Up-To-Date.

= Finde mehr Piestingtal.Source =

Wirf einen Blick in unser [PSOURCE Sortiment](https://n3rds.work/psource_kategorien/psource-plugins/) und hole noch mehr aus Deinem WordPress/ClassicPress!

Halte Dich mit unserem [Newsletter](https://n3rds.work/webmasterservice-n3rdswork-digalize-das-piestingtal/newsletter-management/) über unsere Piestingtal.Source informiert!

== Hilf uns ==

Viele, viele Kaffees konsumieren wir während wir an unseren Plugins und Themes arbeiten.
Wie wärs? Möchtest Du uns mit einer Kaffee-Spende bei der Arbeit an unseren Plugins unterstützen?

== Hilf uns ==

Viele, viele Kaffees konsumieren wir während wir an unseren Plugins und Themes arbeiten.
Wie wärs? Möchtest Du uns mit einer Kaffee-Spende bei der Arbeit an unseren Plugins unterstützen?

= Unterstütze uns =

Mach eine [Spende per Überweisung oder PayPal](https://n3rds.work/spendenaktionen/unterstuetze-unsere-psource-free-werke/) wir Danken Dir!

Halte Dich mit unserem [Newsletter](https://n3rds.work/webmasterservice-n3rdswork-digalize-das-piestingtal/newsletter-management/) über unsere Piestingtal.Source informiert!

== ChangeLog ==

= 2.3.2 = DerN3rd =

* Hotfix Unerwünschte API-Abfrage

= 2.3.1 = DerN3rd =

* Fix Cookie Error