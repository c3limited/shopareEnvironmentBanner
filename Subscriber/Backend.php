<?php

namespace C3EnvironmentBanner\Subscriber;

use Enlight\Event\SubscriberInterface;
use Shopware\Components\DependencyInjection\Container;

class Backend implements SubscriberInterface
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var string
     */
    protected $pluginPath;

    /**
     * @todo Make a configuration that can be adjusted as required
     * @var array
     */
    protected $colorMap = [
        'dev' => '0,255,0',
        'staging' => '255,127,0',
        'preview' => '255,0,255',
        'production' => '255,0,0'
    ];

    /**
     * Backend constructor.
     *
     * @param string    $pluginPath
     * @param Container $container
     */
    public function __construct(
        $pluginPath,
        Container $container
    )
    {
        $this->pluginPath = $pluginPath;
        $this->container = $container;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Backend_Index'
            => 'onPostDispatchSecureBackendIndex'
        ];
    }

    public function onPostDispatchSecureBackendIndex(\Enlight_Event_EventArgs $args)
    {
        // Get environment specific values
        $environment = getenv('SHOPWARE_ENV');
        if (!isset($this->colorMap[$environment])) {
            return;
        }
        $primaryColour = $this->colorMap[$environment];

        // Add backend template
        $this->container->get('Template')->addTemplateDir(
            $this->getPath() . '/Resources/views/'
        );
        $view = $args->getSubject()->View();
        $view->extendsTemplate('backend/c3EnvironmentBanner/index/header.tpl');

        // Set environment-specific values for colour and label
        $view->assign('environment', ucfirst($environment));
        $colVar = $colorVariants = $this->getColorVariants($primaryColour);
        $view->assign('rgbBackGrad1', $colVar['lighter1']);
        $view->assign('rgbBackGrad2', $colVar['lighter2']);
        $view->assign('rgbBackGrad3', $colVar['lighter3']);
        $view->assign('rgbBackGrad4', $colVar['lighter4']);
        $view->assign('rgbBorderTop', $colVar['mid']);
        $view->assign('rgbBorderBottom', $colVar['midDarker']);
    }

    /**
     * Calculate variations of primary colour as strings
     *
     * @param string $primaryColor
     *
     * @return array
     */
    protected function getColorVariants($primaryColor)
    {
        $col = explode(',', $primaryColor);

        // Minimum and maximum values for RGB 0-255 for lightening effects
        $lighterCoEfficients = [
            [171, 238],
            [218, 245],
            [223, 246],
            [198, 246],
        ];

        // Calculate lighter versions of colours based on co-efficients
        $lighter = [];
        for ($i=0; $i<count($lighterCoEfficients); $i++) {
            $lighter[$i] = $col;
            $min = $lighterCoEfficients[$i][0];
            $max = $lighterCoEfficients[$i][1];
            foreach ($lighter[$i] as &$c) {
                $c = ($c / 255) * ($max-$min);
                $c += $min;
            }
        }

        // Calculate darker colours by contrast and brightening
        $darkeningAdjustments = [
            [0.4, 60],
            [0.2, 60]
        ];
        $mid = $col;
        foreach ($mid as &$c) {
            $contrastCoefficient = $darkeningAdjustments[0][0];
            $brightnessAdjust = $darkeningAdjustments[0][1];
            $c *= $contrastCoefficient;
            $c += $brightnessAdjust;
        }
        $midDarker = $col;
        foreach ($midDarker as &$c) {
            $contrastCoefficient = $darkeningAdjustments[1][0];
            $brightnessAdjust = $darkeningAdjustments[1][1];
            $c *= $contrastCoefficient;
            $c += $brightnessAdjust;
        }

        return [
            'mid' =>        implode(',',$mid),
            'midDarker' =>  implode(',',$midDarker),
            'lighter1' =>   implode(',',$lighter[0]),
            'lighter2' =>   implode(',',$lighter[1]),
            'lighter3' =>   implode(',',$lighter[2]),
            'lighter4' =>   implode(',',$lighter[3])
        ];
    }

    protected function getPath()
    {
        return $this->pluginPath;
    }
}
