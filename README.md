# php-examples
Simple site with form, for getting credit card data from customer, creating token, and making payment using Espago test gateway.
This is not all code you need for integration with Espago. This is just example how token mechanism should be used, and what parameters you need to send request to Espago gateway using PHP code.

### Installation

1. Copy files from /payment directory to PHP/Web server. 
2. Change name (or copy file) from espago-config.php.example to espago-config.php
3. Change values in espago-config.php according to your account in Espago gateway.

### Explanation

- `index.html` with payment form includes script https://js.espago.com/espago-1.2.js The main task of Espago JS is to get card data from form, the initial validation of data, sending request to the Espago gateway and getting the token. This token can be used to perform the query on /api/charges or /api/clients, and to achieve this the token is passed to the PHP file as 'card_token' parameter.
- `espago_new_transaction.php` recieve card_token, creates request to Espago API
- `espago-config.php` is config file