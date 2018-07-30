---
title: Install and configure Toplytics
linktitle: Install and configure Toplytics
description: Check out how to install and configure the Presslabs Toplytics plugin to your Google Analytics account with step-by-step detailed explanations and screenshots.
categories: []
keywords: [toplytics]
menu:
  docs:
    parent: ""
weight: 1
draft: false
aliases: []
toc: true
---

You can simply search it in the WordPress plugins, install and activate it, or [download it](https://www.presslabs.org/toplytics/) and upload it in wp-content/plugins and again activate it from WordPress.

As mentioned before, Toplytics displays the most visited posts as a widget using data extracted from Google Analytics, so it needs to be connected to Google Analytics. You need to have Google Analytics active on your site if you want to use this plugin.

This means you need to set up your site in [Google Analytics](https://www.google.com/analytics/). To set up your site in Google Analytics you need to [create an account and to add your site as a property](https://support.google.com/analytics/answer/1008015?hl=en&ref_topic=3544906). Then you need to [set up Analytics tracking on your site](https://support.google.com/analytics/answer/1008080?hl=en). You will have a tracking code you need to copy and paste as the first item into the **HEAD** of every webpage you want to track or you can use the **Google Tag Manager** to help you help you add tags to your site.

{{< img src="../images/analytics_tracking_code" type="png" alt="Get Tracking ID" caption="Get Tracking ID" >}}

We offer two possibilities to use Toplytics: through **Public Authorization** or the **Private Authorization**.

## Public Authorization
This method is using the Presslabs public API key to authenticate you to the Google Analytics API, and you don't have to set up your own API keys.

{{< img src="../images/toplytics_public" type="png" alt="Toplytics Public Authorization" caption="Toplytics Public Authorization" >}}

To use the **Public Authorization** simply press the **Log in with your Google Account via Presslabs.org** button and you will be redirected to the Google Authorization screen where you will be asked for read access to your analytics profiles.

{{< img src="../images/toplytics_permission" type="png" alt="Allow your domain to access your Google account" caption="Allow your domain to access your Google account" >}}

Then you need to select your profile.

{{< img src="../images/toplytics_select_profile" type="png" alt="Select your Analytics Profile" caption="Select your Analytics Profile" >}}

In case you have no user profile set up in your Analytics account, a warning message will appear:

{{< img src="../images/toplytics_no_profile" type="png" alt="No user profile warning" caption="No user profile warning" >}}

Now you are all set to [use Toplytics]({{< ref "usage.md" >}}) to display your most visited posts.

{{< img src="../images/toplytics_done" type="png" alt="Toplytics public configuration done" caption="Toplytics public configuration done" >}}

## Private Authorization
The private authorization is the recommanded way in using Toplytics, as it offers you complete control over the connection by using your very own API keys and application for granting access.

{{< img src="../images/toplytics_private_authorize" type="png" alt="Toplytics Private Authorization" caption="Toplytics Private Authorization" >}}

You need to enter your Client ID and Client Secret from your Google Analytics account. The next steps will guide you in configuring your Google Analytics account to Toplytics. Keep in mind that you will need the **Redirect URL** mentioned in this page further in configuring Toplytics.

### Step 1: Register client application with Google

Every application has to be registered with the Google API so that we can use the OAuth 2.0 token during the authentication and authorisation process. To register an application the user has to login to the Google account and go to [Google API Dashboard](https://console.developers.google.com/).

{{< img src="../images/toplytics_google" type="png" alt="Google API Dashboard" caption="Google API Dashboard" >}}

#### 1.1. Create new project

From Google API console create a new project using the “Create Project” button. To set up properly the client application, select a unique “project name”.

{{< img src="../images/toplytics_create_project" type="png" alt="Creating a new project" caption="Creating a new project" >}}

#### 1.2. Enable the Analytics API
From the Google API Dashboard go to **Enable APIs and Services** and browse the library to find the **Analytics API**, then click it and enable it.

{{< img src="../images/toplytics_enable_api" type="png" alt="Enable API's and Services" caption="Enable API's and Services" >}}

{{< img src="../images/toplytics_enable_api2" type="png" alt="Search for the Analytics API" caption="Search for the Analytics API" >}}

{{< img src="../images/toplytics_enable_api3" type="png" alt="Enable the Analytics API" caption="Enable the Analytics API" >}}

#### 1.3. Create new Client ID
Go to the **Credentials -> OAuth consent screen** tab to set up your product name.

{{< img src="../images/toplytics_consent_screen" type="png" alt="Set up your product name" caption="Set up your product name" >}}

After you set up your product name, you can create your credentials. Go back to the **Dashboard** section, click on the arrow of the button **Create credentials** and choose the **OAuth Client ID** option.

{{< img src="../images/toplytics_credentials" type="png" alt="Go to OAuth Client ID" caption="Go to OAuth Client ID" >}}

When asked to choose your application type choose the **Web application** option. You will be asked to introduce the **Javascript Origins** and **Redirects URI's**. As **Authorized JavaScipt Origins** introduce your domain name, and as **Authorized redirect URI** you need to introduce the [Redirect URL](./installation/#private-authorization) from `Settings -> Toplytics -> Private Authorization`.

{{< img src="../images/toplytics_create_client_ID" type="png" alt="Create OAuth Client ID" caption="Create OAuth Client ID" >}}

Your newly created credentials will appear on the **Credentials** page and the **Client ID** and **Client secret** you need to authorize the **Private Authentification** will appear in a pop up. You can also see them by pressing the **Edit OAuth Client** button from the Credentials section.

{{< img src="../images/toplytics_get_credentials" type="png" alt="Credentials section" caption="Credentials section" >}}

### Step 2: Authorising Requests

Copy the Client ID and the Client Secret keys from the **Credentials section**, then go back to `Settings -> Toplytics -> Private Authorization` to paste these credentials. By using these keys the client application will avoid sharing the username and/or password with any other Toplytics users.

{{< img src="../images/toplytics_private" type="png" alt="Private Authorization" caption="Private Authorization" >}}

Click the **Private Authorize** button and after logging in you need to agree that the newly created app will access your Analytics data and you are all set.

{{< img src="../images/toplytics_permission" type="png" alt="Allow your domain to access your Google account" caption="Allow your domain to access your Google account" >}}

You can select from the list of profiles the one you want to use for this site or you can disconnect your Google account. Make sure you have a Google Analytics profile set up, otherwise a warning message will appear that there are no profiles on the selected Google account.

{{< img src="../images/toplytics_select_profile" type="png" alt="Select your Analytics Profile" caption="Select your Analytics Profile" >}}

Now you are all set to [use Toplytics]({{< ref "usage.md" >}}) to display your most visited posts.

{{< img src="../images/toplytics_done_private" type="png" alt="Toplytics private configuration done" caption="Toplytics private configuration done" >}}
