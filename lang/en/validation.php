<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */
    'accepted'             => 'The :Attribute must be accepted.',
    'accepted_if'          => 'The :Attribute must be accepted when :other is :value.',
    'active_url'           => 'The :Attribute is not a valid URL.',
    'after'                => 'The :Attribute must be a date after :date.',
    'after_or_equal'       => 'The :Attribute must be a date after or equal to :date.',
    'alpha'                => 'The :Attribute may only contain letters.',
    'alpha_dash'           => 'The :Attribute may only contain letters, numbers, and dashes.',
    'alpha_spaces'         => 'The :Attribute may only contain alphanumeric characters.',
    'alpha_num'            => 'The :Attribute may only contain letters and numbers.',
    'array'                => 'The :Attribute must be an array.',
    'before'               => 'The :Attribute must be a date before :date.',
    'before_or_equal'      => 'The :Attribute must be a date before or equal to :date.',
    'between'              => [
        'numeric' => 'The :Attribute must be between :min and :max.',
        'file'    => 'The :Attribute must be between :min and :max kilobytes.',
        'string'  => 'The :Attribute must be between :min and :max characters.',
        'array'   => 'The :Attribute must have between :min and :max items.',
    ],
    'boolean'              => 'The :Attribute field must be true or false.',
    'confirmed'            => 'The :Attribute confirmation does not match.',
    'current_password'     => 'The password is incorrect.',
    'date'                 => 'The :Attribute is not a valid date.',
    'date_equals'          => 'The :Attribute must be a date equal to :date.',
    'date_format'          => 'The :Attribute does not match the format :format.',
    'declined'             => 'The :Attribute must be declined.',
    'declined_if'          => 'The :Attribute must be declined when :other is :value.',
    'different'            => 'The :Attribute and :other must be different.',
    'digits'               => 'The :Attribute must be :digits digits.',
    'digits_between'       => 'The :Attribute must be between :min and :max digits.',
    'dimensions'           => 'The :Attribute has invalid image dimensions.',
    'distinct'             => 'The :Attribute field has a duplicate value.',
    'email'                => 'The :Attribute must be a valid email address.',
    'ends_with'            => 'The :Attribute must end with one of the following: :values.',
    'enum'                 => 'The selected :Attribute is invalid.',
    'exists'               => 'The selected :Attribute is invalid.',
    'file'                 => 'The :Attribute must be a file.',
    'filled'               => 'The :Attribute field must have a value.',
    'gt'                   => [
        'numeric' => 'The :Attribute must be greater than :value.',
        'file'    => 'The :Attribute must be greater than :value kilobytes.',
        'string'  => 'The :Attribute must be greater than :value characters.',
        'array'   => 'The :Attribute must have more than :value items.',
    ],
    'gte'                  => [
        'numeric' => 'The :Attribute must be greater than or equal to :value.',
        'file'    => 'The :Attribute must be greater than or equal to :value kilobytes.',
        'string'  => 'The :Attribute must be greater than or equal to :value characters.',
        'array'   => 'The :Attribute must have :value items or more.',
    ],
    'image'                => 'The :Attribute must be an image.',
    'in'                   => 'The selected :Attribute is invalid.',
    'in_array'             => 'The :Attribute field does not exist in :other.',
    'integer'              => 'The :Attribute must be an integer.',
    'ip'                   => 'The :Attribute must be a valid IP address.',
    'ipv4'                 => 'The :Attribute must be a valid IPv4 address.',
    'ipv6'                 => 'The :Attribute must be a valid IPv6 address.',
    'json'                 => 'The :Attribute must be a valid JSON string.',
    'lt'                   => [
        'numeric' => 'The :Attribute must be less than :value.',
        'file'    => 'The :Attribute must be less than :value kilobytes.',
        'string'  => 'The :Attribute must be less than :value characters.',
        'array'   => 'The :Attribute must have less than :value items.',
    ],
    'lte'                  => [
        'numeric' => 'The :Attribute must be less than or equal to :value.',
        'file'    => 'The :Attribute must be less than or equal to :value kilobytes.',
        'string'  => 'The :Attribute must be less than or equal to :value characters.',
        'array'   => 'The :Attribute must not have more than :value items.',
    ],
    'mac_address'          => 'The :Attribute must be a valid MAC address.',
    'max'                  => [
        'numeric' => 'The :Attribute may not be greater than :max.',
        'file'    => 'The :Attribute may not be greater than :max kilobytes.',
        'string'  => 'The :Attribute may not be greater than :max characters.',
        'array'   => 'The :Attribute may not have more than :max items.',
    ],
    'mimes'                => 'The :Attribute must be a file of type: :values.',
    'mimetypes'            => 'The :Attribute must be a file of type: :values.',
    'min'                  => [
        'numeric' => 'The :Attribute must be at least :min.',
        'file'    => 'The :Attribute must be at least :min kilobytes.',
        'string'  => 'The :Attribute must be at least :min characters.',
        'array'   => 'The :Attribute must have at least :min items.',
    ],
    'multiple_of'          => 'The :Attribute must be a multiple of :value.',
    'not_in'               => 'The selected :Attribute is invalid.',
    'not_regex'            => 'The :Attribute format is invalid.',
    'numeric'              => 'The :Attribute must be a number.',
    'password'             => 'The password is incorrect.',
    'present'              => 'The :Attribute field must be present.',
    'prohibited'           => 'The :Attribute field is prohibited.',
    'prohibited_if'        => 'The :Attribute field is prohibited when :other is :value.',
    'prohibited_unless'    => 'The :Attribute field is prohibited unless :other is in :values.',
    'prohibits'            => 'The :Attribute field prohibits :other from being present.',
    'regex'                => 'The :Attribute format is invalid.',
    'required'             => 'The :Attribute field is required.',
    'required_if'          => 'The :Attribute field is required when :other is :value.',
    'required_unless'      => 'The :Attribute field is required unless :other is in :values.',
    'required_with'        => 'The :Attribute field is required when :values is present.',
    'required_with_all'    => 'The :Attribute field is required when :values is present.',
    'required_without'     => 'The :Attribute field is required when :values is not present.',
    'required_without_all' => 'The :Attribute field is required when none of :values are present.',
    'same'                 => 'The :Attribute and :other must match.',
    'size'                 => [
        'numeric' => 'The :Attribute must be :size.',
        'file'    => 'The :Attribute must be :size kilobytes.',
        'string'  => 'The :Attribute must be :size characters.',
        'array'   => 'The :Attribute must contain :size items.',
    ],
    'starts_with'          => 'The :Attribute must start with one of the following: :values.',
    'string'               => 'The :Attribute must be a string.',
    'timezone'             => 'The :Attribute must be a valid zone.',
    'unique'               => 'The :Attribute has already been taken.',
    'uploaded'             => 'The :Attribute failed to upload.',
    'url'                  => 'The :Attribute format is invalid.',
    'uuid'                 => 'The :Attribute must be a valid UUID.',

    'custom' => [
        'path' => [
            'filemanager.drive_from_path' => 'Invalid value specified for: :Attribute',
        ],
        'filename' => [
            'filemanager' => [
                'filename_is_valid' => 'Invalid value specified for: :Attribute',
                'store_only_html_to_templates' => 'The file has an incorrect extension. Please check the file and upload again.',
                'do_not_store_exe_in_public' => 'The file has an incorrect extension. Please check the file and upload again.',
                'do_not_store_php_in_public' => 'The upload of php files was disabled.',
            ],
        ],
        'processFile' => [
            'filemanager' => [
                'file_is_not_used_at_email_events' => 'You can not delete the template :path because it has a relationship with Email Event',
                'file_is_not_used_as_routing_screen' => 'You can not delete the template :path because it is used as a routing screen.',
            ],
        ],
        'processCategory' => [
            'process_category_manager' => [
                'category_does_not_have_processes' => 'The category cannot be deleted while it is still assigned to processes.',
            ],
        ],
    ],

    'attributes' => [
        'title' => 'name',
        'firstname' => 'First Name',
        'lastname' => 'Last Name',
        'config' => 'Config',
    ],
];
