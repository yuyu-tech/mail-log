<?php

return [
    /**
     * Log Status
     */
    'status' => [
        'pending' => 1,
        'sent' => 2,
        'error' => 3,
        'confirmed' => 4,
    ],

    /**
     * Filter address type
     */
    'address' => [
        'type' => [
            'email' => 1,
            'domain' => 2
        ]
    ],

    /**
     * Parameters Value
     */
    'combo' => [
        'yes_no' => [
            'no' => 0,
            'yes' => 1
        ],
    ],
    
    /**
     * Send a mail if all addresses are valid.
     */
    'send_if_all_valid' => false,

    /**
     * Consider recipient address as a valid address if any filter rule is not applied to it.
     */
    'allow_if_not_in_filter_list' => true,

    /**
     * Database connection name. If we want to separate a log database from application database.
     * 
     * Default it will use application database.
     */
    'database' => null,

    /**
     * Error Description
     */
    'errors' => [
        'TO_ADDRESS_NOT_FOUNT' => 'No Recipient address found after filter.',
        'TO_ADDRESS_MODIFIED' => 'To addresses list modified.',
        'CC_ADDRESS_MODIFIED' => 'CC addresses list modified.',
        'BCC_ADDRESS_MODIFIED' => 'BCC addresses list modified.',
        'INVALID_MAILER_CONNECTION' => 'Error occurred while connecting to mail server.'
    ],

    /**
     * SMTP connection error update interval in minutes
     */
    'interval' => 1,
];
