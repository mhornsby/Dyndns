# Linode-PHP-Dydns-Server-

A Dydns server using the Linode API based on the work of 
  - Nico Kaiser https://github.com/nicokaiser/Dyndns 
  - and Travis Maynard https://travismaynard.com/writing/dynamic-dns-using-the-linode-api

Implements a Dyndns compatible server 
  - https://help.dyn.com/remote-access-api/perform-update/
  - https://help.dyn.com/remote-access-api/return-codes/

##On your Linode
- go to the DNS manager and create a A/AAA record e.g. home.example.com
- got go Accounts/Users and Permisions and create a new user https://manager.linode.com/user/edit
- set the user to a restricted user "Yes - this user can only do what I specify"
- go to Accounts/Users Edit Permisions and disable all permissions except "DNS Zone Grants" for the domain
- login the linode as the new user and create an API key using "my profile" "API Keys" https://manager.linode.com/profile/api

##Obtaining your Linode DomainID and Resource ID

To find your Domain ID, simply paste your API key into the following URL and view it in your browser.
`https://api.linode.com/?api_key=your-api-key&api_action=domain.list`

This will return a JSON object listing all of the domains that are registered on your Linode account. Simply locate the domain that you will be using for dynamic dns and take note of the value in the DOMAINID property.

To find your Resource ID, simply input your API key and your newly obtained Domain ID in the following URL and view it in your browser.
`https://api.linode.com/?api_key=your-api-key&api_action=domain.resource.list&domainid=your-domain-id`

This will also return a JSON object. Find the record that contains the A/AAA record you created using the linode DNS manager and note the Resource ID.

##Installation

### nic subfolder
Create a subfolder `nic` on your web server WWW folder e.g. `http://www.example.com/nic`

Change directory to the `nic` folder.

### Install Composer
```
sudo apt-get update
sudo apt-get install curl php5-cli git
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
```

### Add Dyndns as a dependency
` composer require mhornsby/dyndns:*`

### Setup the `nic` folder
```
cp vendor/mhornsby/dyndns/nic/update.php .
cp vendor/mhornsby/dyndns/nic/htaccess .htaccess
```

### Setup /etc/dyndns
```
sudo mkdir /etc/dyndns`
sudo cp -r vendor/mhornsby/dyndns/etc:dyndns /etc/dyndns`
cd /etc/dyndns
```

- setup linode.api_key` enter the Linode API key into
- setup `linode.resourceid` e.g. home.example.com:your-resource-id
- setup `linode.domainidid` e.g. *.example.com:your-domainid-id
- setup `dyndns.host` e.g. *.example.com:user1
- setup `dyndns.user` e.g. htpasswd -c -d /etc/dyndns/dyndns.user user1

### Testing
Test your setup with
` user1:password@example.com/nic/update?hostname=home.example.com&myip=1.2.3.4`

### Dyndns Return Codes
- `good` The update was successful, and the hostname is now updated.
- `badauth` The username and password pair do not match a real user.
- `notfqdn` The hostname specified is not a fully-qualified domain name (not in the form hostname.dyndns.org or domain.com).
- `nohost` The hostname specified does not exist in this user account (or is not in the service specified in the system parameter)
- `badagent` The user agent was not sent or HTTP method is not permitted (we recommend use of GET request method).
- `dnserr` DNS error encountered
- `911` There is a problem or scheduled maintenance on our side.

### Debug
Debuging is enabled by default in `nic/update.php` via these two lines

```
->setConfig('debug', true)  // enable debugging
->setConfig('debugFile', '/tmp/dyndns.log') // debug file
```
### Implemented fields

- `hostname` Comma separated list of hostnames that you wish to update (up to 20 hostnames per request). This is a required field. Example: `hostname=dynhost1.yourdomain.tld,dynhost2.yourdomain.tld`
` `myip` IP address to set for the update. Defaults to the best IP address the server can determine.





  
  `




















