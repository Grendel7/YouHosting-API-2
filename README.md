# YouHosting API v2
YouHosting API v2 is an effort to combine the features of the YouHosting SVIP library "[libyouhosting](https://github.com/i7Grendel/libyouhosting)" and the older [cURL API](https://bitbucket.org/grendelhosting/youhosting-api) into one unified API. This allows SVIP resellers to use all the advantages of their API while bringing (largely) the same functionality to all resellers by crawling the YouHosting website.

## How do I use this API in my software?
First of all, you need to install it. The recommended way is to use Composer: `composer require i7grendel/youhosting2`

In your application, you can use the API as follows:
```php
$resellerEmail = "foo@example.com";
$resellerPassword = "password123";
$options = array(); // An array of options. See the relevant section

$apiKey = "sdfjklasdfjklasdfjkljklasdf"; // This is optional. If you don't have an API key, just leave it out in the next line

$youhosting = new \YouHosting\YouHosting($resellerEmail, $resellerPassword, $options, $apiKey);

$clientId = 123456;
// The search features are quite flexible. You can enter a client ID, a client email or an instance of \YouHosting\Client which contains at least either of those. Having the client ID is highly recommended though because searching is very slow.
$client = $youhosting->getClient($clientId);
// The API returns it's information as a data container (see \YouHosting\Client for example), so you can easily see which data is in it.

```

If this library is picked up, I will probably document every method. For now, just check the methods yourself in the YouHosting class.

### Options
These are the options you can set using the options array.

* 'web_url': The url for the web API. Defaults to http://www.youhosting.com
* 'cookie_type': Select how cookies for the web API are stored. Defaults to array, but can also be 'file' or 'session'.
* 'cookie_file_name': If the cookie type is file, this must contain the path to a plain text file to which cookies can be written.
* 'cookie_session_key': If the cookie type is session, this must contain the PHP session key.
* 'api_url': The url for the REST API. Defaults to https://rest.main-hosting.com
* 'verify_ssl': Whether the SSL certificate should be verified. Defaults to false, because rest.main-hosting.com has a self signed certificate.

## Differences with the SVIP API
Many (but not all) functions of the website have been emulated in a REST API, which is only for SVIP resellers. If you are an SVIP reseller, using the API is highly recommended because it is faster and creates much more detailed error messages.

These methods are optimized when using the REST API:
* listClients() and listAccounts(). These are very slow when using the web API but pretty fast when using the REST API. This is because the API automatically includes all client details whereas the web API requires additional requests for every single client or account. You can also see the total number of result pages and the total number of clients, which is not possible with the web API.
* getAccount() and getClient().
* getClientLoginUrl() and getAccountLoginUrl().
* suspendAccount() and unsuspendAccount().
* checkDomain()
* getSubdomains(), getPlans(), and getNameservers().

All other API features have either not been implemented or are broken in the SVIP API.

## Final Notes
This is an alpha release. While the SVIP API components are quite solid (they are largely copied from libyouhosting and are quite simple), the Web API components are a bit more hacky and could behave in unexpected ways. Use with caution.

If you are missing anything in this API or found any bugs, please let me know by opening an issue. And of course, contributions are highly appreciated!
