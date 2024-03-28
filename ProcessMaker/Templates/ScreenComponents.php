<?php

namespace ProcessMaker\Templates;

class ScreenComponents
{
    public static function getComponents()
    {
        return [
            'CSS' => null,
            'Layout' => [
                'FormMultiColumn',
                'BFormComponent' => [
                    'bootstrapComponent' => [
                        'b-table',
                    ],
                ],
            ],
            'Fields' => [
                'AiSection',
                'FormInput',
                'FormSelectList',
                'FormTextArea',
                'FormHtmlViewer',
                'FormDatePicker',
                'FormCheckbox',
                'FormRichText',
                'FormImage',
                'FormRecordList',
                'FormLoop',
                'FormNestedScreen',
                'FormButton',
                'FileUpload',
                'FileDownload',
                'BFormComponent' => [
                    'bootstrapComponent' => [
                        'b-img',
                        'b-embed',
                        'b-form-rating',
                        'b-form-spinbutton',
                        'b-form-timepicker',
                        'b-form-tags',
                    ],
                ],
                'BWrapperComponent' => [
                    'bootstrapComponent' => [
                        'b-modal',
                        'b-alert',
                        'b-card',
                        'b-collapse',
                        'b-jumbotron',
                    ],
                ],
                'PhotoVideo',
                'SignaturePad',
                'Captcha',
                'Viewer',
                'GooglePlaces',
                'SavedSearchChart',
            ],
        ];
    }
}
