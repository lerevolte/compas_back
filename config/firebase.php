<?php

declare(strict_types=1);

return [
    /*
     * ------------------------------------------------------------------------
     * Default Firebase project
     * ------------------------------------------------------------------------
     */

    'default' => env('FIREBASE_PROJECT', 'app'),

    /*
     * ------------------------------------------------------------------------
     * Firebase project configurations
     * ------------------------------------------------------------------------
     */

    'projects' => [
        'app' => [

            /*
             * ------------------------------------------------------------------------
             * Credentials / Service Account
             * ------------------------------------------------------------------------
             *
             * In order to access a Firebase project and its related services using a
             * server SDK, requests must be authenticated. For server-to-server
             * communication this is done with a Service Account.
             *
             * If you don't already have generated a Service Account, you can do so by
             * following the instructions from the official documentation pages at
             *
             * https://firebase.google.com/docs/admin/setup#initialize_the_sdk
             *
             * Once you have downloaded the Service Account JSON file, you can use it
             * to configure the package.
             *
             * If you don't provide credentials, the Firebase Admin SDK will try to
             * auto-discover them
             *
             * - by checking the environment variable FIREBASE_CREDENTIALS
             * - by checking the environment variable GOOGLE_APPLICATION_CREDENTIALS
             * - by trying to find Google's well known file
             * - by checking if the application is running on GCE/GCP
             *
             * If no credentials file can be found, an exception will be thrown the
             * first time you try to access a component of the Firebase Admin SDK.
             *
             */

            //'credentials' => env('FIREBASE_CREDENTIALS', env('GOOGLE_APPLICATION_CREDENTIALS')),
            'credentials' => [
                'type' => 'service_account',
                'project_id' => 'compas-c4e94',
                'private_key_id' => '17edfffb3c6f33a54af38a4effa07f528cfc5dc4',
                'private_key' => '-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDcUL/mLSHM0T6I\n0Vyj71J+xIF7AYN72DZClK3DJqR7VUSMjAqGBm2QylDMVA7/DD0Y5U2FLQmxMJ8z\njqtfVFwG1hWCQPPV08MY6JJJmVL2VOQYRVkV7IzKzYTeYO5cuaf14EgHXllB0Mto\nqR3gqVmZbZwFGYeUrOzBIMrlwvjriTsUrBq2l61Cs2lM8ewM4M/E+Thk36r+TQR2\n/B95LqyzAPigeB64afJlng78T+puaz4ftipXUAsdD2nuMl6kp+EgK91E+lFNQGTP\n5YZ5H/kW743jstx1u8ucIEkVz01bEw3da9HDZwDjoG16SJE70u5zjrJhi+tSlrAs\n2OgoGF0LAgMBAAECggEADFzVn9dpfaJCNZA1+Bb+VJG3SE058RGdXdgU9g3tjeQn\ngBF9p66lqEnKBeUzoDkyFnsLgg0YbaJyVITNdgB4V4Pc2h3F8Po8iOHID6w0XRE/\naWM+z/2hmuG0cnwS9A4Q8DmhIRS4wnoyZ9GRlSE7n2HortqTYpLfiCjMad8qc6D5\nkRgL5FLgRxUXTM7lA6B5GurVL9bP8miEsAGJUnNuo++rymV1eIonTmimOPdL3dmY\ncP/A/4J0CyChjokjqUhj7+fEJs8gHNUM9quohB7BtmkLOeNgsXlkIOowY6bGSzDj\nqpuBFBzjjNUJwIZJwY/wvhLxRx1UIU+UpZRCDRoZbQKBgQDpe/7wZ80MoOVkLKhH\nVWsKYRQWTVopMDnHM4ZFWwjRb1u3VGgz1aCWZgllNFyZzJpCizznPlgm0g2Z0t59\nTFaIzn2b4508ztfu3rwQweUieNbA1xaG7MG2L3ZPxbWhSge8E/4q5eMlzXhABM7l\nL8Ggw8M8mP6KG/pXVk7cy8ptNwKBgQDxj6dahgKokh3E803v3KJOkb086mC7UwmX\nYA42AdUrU+nF1asyjVQbmjpd3E+QLZnUT9thkcjCqh7UGQ01uITF+RWpKtZ0dSNi\nb2VQR5ZSIiJLKDRuiUjGApZSbZHcqJPxpvVQS0LuSMJUH9B1lfTPjD6k8SECxjTn\n4XCyRCxYzQKBgGbnM1EZLpImR3ODCxeFWgvVBVle9iG5E33sA32Fxbkoby+5j8No\nn8IpbnKgRT+zoTk6zLjODWPup/fnrA9lGa5p84pFIwOt1hV9LT3ldutefqe3JEKL\nDkURXf+Cj65qRkZqOVcGwrnieOEePWFdTuVf4ihO9cBA3HCppVBCsRC5AoGAKVN2\nQhpjIgu9ZBBr6PlKGz77rQw7+FBAd9FLHRo7Kep1OA9R9pgPJ5m/GoeyZOwoQRZY\nZdzXB9aq54ZRqDslG+l9Ny/I+KuBnjmIj69vnCWS0GDUd4StGpMevINaTPHaPaFe\nnpoBnVp9RH8c3sEE3O0VWoqWJy9ZpcH/0b+wBpECgYEA4ydAuL3ZK81SviQ5Ytzk\nhV4xyjR5P1siPEcAIs+fBV0Cyu6bSka2louTbD6z8xsiaVBbe0hkEhi3QF9FQuPk\ne8woW402lwzYKTkbXWsBfOvKOeKL6YhUyEWo0YBfFEv9jY3NOf/7+F4/nbk7AqYT\n+imwlKmm3ez9GuOjy6QnMN8=\n-----END PRIVATE KEY-----\n',
                'client_email' => 'firebase-adminsdk-llk75@compas-c4e94.iam.gserviceaccount.com',
                'client_id' => '104159335342342320184',
                'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                'token_uri' => 'https://oauth2.googleapis.com/token',
                'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-llk75%40compas-c4e94.iam.gserviceaccount.com',
                'universe_domain' => 'googleapis.com',
            ],

            /*
             * ------------------------------------------------------------------------
             * Firebase Auth Component
             * ------------------------------------------------------------------------
             */

            'auth' => [
                'tenant_id' => env('FIREBASE_AUTH_TENANT_ID'),
            ],

            /*
             * ------------------------------------------------------------------------
             * Firestore Component
             * ------------------------------------------------------------------------
             */

            'firestore' => [

                /*
                 * If you want to access a Firestore database other than the default database,
                 * enter its name here.
                 *
                 * By default, the Firestore client will connect to the `(default)` database.
                 *
                 * https://firebase.google.com/docs/firestore/manage-databases
                 */

                // 'database' => env('FIREBASE_FIRESTORE_DATABASE'),
            ],

            /*
             * ------------------------------------------------------------------------
             * Firebase Realtime Database
             * ------------------------------------------------------------------------
             */

            'database' => [

                /*
                 * In most of the cases the project ID defined in the credentials file
                 * determines the URL of your project's Realtime Database. If the
                 * connection to the Realtime Database fails, you can override
                 * its URL with the value you see at
                 *
                 * https://console.firebase.google.com/u/1/project/_/database
                 *
                 * Please make sure that you use a full URL like, for example,
                 * https://my-project-id.firebaseio.com
                 */

                'url' => env('FIREBASE_DATABASE_URL'),

                /*
                 * As a best practice, a service should have access to only the resources it needs.
                 * To get more fine-grained control over the resources a Firebase app instance can access,
                 * use a unique identifier in your Security Rules to represent your service.
                 *
                 * https://firebase.google.com/docs/database/admin/start#authenticate-with-limited-privileges
                 */

                // 'auth_variable_override' => [
                //     'uid' => 'my-service-worker'
                // ],

            ],

            'dynamic_links' => [

                /*
                 * Dynamic links can be built with any URL prefix registered on
                 *
                 * https://console.firebase.google.com/u/1/project/_/durablelinks/links/
                 *
                 * You can define one of those domains as the default for new Dynamic
                 * Links created within your project.
                 *
                 * The value must be a valid domain, for example,
                 * https://example.page.link
                 */

                'default_domain' => env('FIREBASE_DYNAMIC_LINKS_DEFAULT_DOMAIN'),
            ],

            /*
             * ------------------------------------------------------------------------
             * Firebase Cloud Storage
             * ------------------------------------------------------------------------
             */

            'storage' => [

                /*
                 * Your project's default storage bucket usually uses the project ID
                 * as its name. If you have multiple storage buckets and want to
                 * use another one as the default for your application, you can
                 * override it here.
                 */

                'default_bucket' => env('FIREBASE_STORAGE_DEFAULT_BUCKET'),

            ],

            /*
             * ------------------------------------------------------------------------
             * Caching
             * ------------------------------------------------------------------------
             *
             * The Firebase Admin SDK can cache some data returned from the Firebase
             * API, for example Google's public keys used to verify ID tokens.
             *
             */

            'cache_store' => env('FIREBASE_CACHE_STORE', 'file'),

            /*
             * ------------------------------------------------------------------------
             * Logging
             * ------------------------------------------------------------------------
             *
             * Enable logging of HTTP interaction for insights and/or debugging.
             *
             * Log channels are defined in config/logging.php
             *
             * Successful HTTP messages are logged with the log level 'info'.
             * Failed HTTP messages are logged with the log level 'notice'.
             *
             * Note: Using the same channel for simple and debug logs will result in
             * two entries per request and response.
             */

            'logging' => [
                'http_log_channel' => env('FIREBASE_HTTP_LOG_CHANNEL'),
                'http_debug_log_channel' => env('FIREBASE_HTTP_DEBUG_LOG_CHANNEL'),
            ],

            /*
             * ------------------------------------------------------------------------
             * HTTP Client Options
             * ------------------------------------------------------------------------
             *
             * Behavior of the HTTP Client performing the API requests
             */

            'http_client_options' => [

                /*
                 * Use a proxy that all API requests should be passed through.
                 * (default: none)
                 */

                'proxy' => env('FIREBASE_HTTP_CLIENT_PROXY'),

                /*
                 * Set the maximum amount of seconds (float) that can pass before
                 * a request is considered timed out
                 *
                 * The default time out can be reviewed at
                 * https://github.com/kreait/firebase-php/blob/6.x/src/Firebase/Http/HttpClientOptions.php
                 */

                'timeout' => env('FIREBASE_HTTP_CLIENT_TIMEOUT'),

                'guzzle_middlewares' => [],
            ],
        ],
    ],
];
