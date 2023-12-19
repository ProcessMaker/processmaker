<?php

namespace ProcessMaker\Http\Controllers\Saml;

use CodeGreenCreative\SamlIdp\Http\Controllers\MetadataController as SamlIdpMetadataController;
use DateTime;
use Illuminate\Support\Facades\View;

class MetadataController extends SamlIdpMetadataController
{
    public function __construct()
    {
        $validUntil = $this->getValidUntil();
        $cacheDuration = $this->getCacheDuration();

        View::share([
            'saml_valid_until' => $validUntil,
            'saml_cache_duration' => $cacheDuration,
        ]);
    }

    /**
     * The function returns the current date and time plus one year.
     *
     * @return the current date and time plus one year in the format 'Y-m-d\TH:i:s\Z'.
     */
    protected function getValidUntil()
    {
        return date('Y-m-d\TH:i:s\Z', strtotime('+1 year'));
    }

    /**
     * The getCacheDuration function calculates the duration in seconds between the current time
     *
     * @return a string representing the duration in seconds
     */
    protected function getCacheDuration()
    {
        $now = new DateTime();
        $oneMonthFromNow = new DateTime('+1 month');
        $interval = $now->diff($oneMonthFromNow);
        $seconds = $interval->days * 24 * 60 * 60;

        return 'PT' . $seconds . 'S';
    }
}
