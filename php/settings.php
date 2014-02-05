<?php
global $wpsf_settings;
// Affiliate settings
$wpsf_settings[] = array(
    'section_id' => 'ppc',
    'section_title' => 'Pay Per Click',
    'section_description' => '',
    'section_order' => 4,
    'fields' => array(
        array(
            'id' => 'redirect',
            'title' => 'Redirect',
            'desc' => 'Turn the PPC redirect on or off here...',
            'type' => 'radio',
            'std' => 'off',
            'choices' => array(
                'on' => 'ON',
                'off' => 'OFF'
            )
        ),
        array(
            'id' => 'offerlink',
            'title' => 'Offer Link',
            'desc' => 'Offer link url. In iMobiTrax, this would be your go.php link.',
            'type' => 'text',
            'std' => ''
        ),
        array(
            'id' => 'param',
            'title' => 'Param',
            'desc' => 'A string that always appears in your PPC landing page query string.',
            'type' => 'text',
            'std' => 'utm_source'
        )
    )
);
?>
