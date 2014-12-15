<?php

/*
 * This file is part of the Liip/ThemeBundle
 *
 * (c) Liip AG
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\ThemeBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

use Liip\ThemeBundle\Helper\DeviceDetectionInterface;
use Liip\ThemeBundle\ActiveTheme;

/**
 * Listens to the request and changes the active theme based on a cookie.
 *
 * @author Tobias Ebnöther <ebi@liip.ch>
 * @author Pascal Helfenstein <pascal@liip.ch>
 */
class ThemeRequestListener
{
    /**
     * @var ActiveTheme
     */
    protected $activeTheme;

    /**
     * @var array
     */
    protected $cookieOptions;

    /**
     * @var DeviceDetectionInterface
     */
    protected $autoDetect;

    /**
     * @var string
     */
    protected $newTheme;

    /**
     * @param ActiveTheme              $activeTheme
     * @param array                    $cookieOptions The options of the cookie we look for the theme to set
     * @param DeviceDetectionInterface $autoDetect    If to auto detect the theme based on the user agent
     */
    public function __construct(ActiveTheme $activeTheme, array $cookieOptions = null, DeviceDetectionInterface $autoDetect = null)
    {
        $this->activeTheme = $activeTheme;
        $this->autoDetect = $autoDetect;
        $this->cookieOptions = $cookieOptions;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            $cookieValue = null;
            if (null !== $this->cookieOptions) {
                $cookieValue = $event->getRequest()->cookies->get($this->cookieOptions['name']);
            }

            if (!$cookieValue && $this->autoDetect instanceof DeviceDetectionInterface) {
                $cookieValue = $this->getThemeType($event->getRequest());
            }

            if ($cookieValue && $cookieValue !== $this->activeTheme->getName()
                && in_array($cookieValue, $this->activeTheme->getThemes())
            ) {
                $this->activeTheme->setName($cookieValue);
                // store into cookie
                if ($this->cookieOptions) {
                    $this->newTheme = $cookieValue;
                }
            }
        }
    }

    /**
     * Given the Request return the device type.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return string the user agent type
     */
    private function getThemeType(Request $request)
    {
        $mobileCacheHeader = $request->server->get('HTTP_CLOUDFRONT_IS_MOBILE_VIEWER');
        $tabletCacheHeader = $request->server->get('HTTP_CLOUDFRONT_IS_TABLET_VIEWER');
        $desktopCacheHeader = $request->server->get('HTTP_CLOUDFRONT_IS_DESKTOP_VIEWER');

if($tabletCacheHeader == 'true'){
                $this->autoDetect->setUserAgent("Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5355d Safari/8536.25"); 
		   return $this->autoDetect->getType();
		}
elseif($mobileCacheHeader == 'true'){
$this->autoDetect->setUserAgent("Mozilla/5.0 (iPhone; U; CPU iPhone OS 4_3_2 like Mac OS X; en-us) AppleWebKit/533.17.9 (KHTML, like Gecko) Version/5.0.2 Mobile/8H7 Safari/6533.18.5"); 
return $this->autoDetect->getType();
}
        elseif($desktopCacheHeader=='true') {
			$this->autoDetect->setUserAgent("Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/38.0.2125.111 Safari/537.36"); 
		    return $this->autoDetect->getType();
		}
        else { 
	        $userAgent = $request->server->get('HTTP_USER_AGENT');
	        $this->autoDetect->setUserAgent($userAgent);
	        return $this->autoDetect->getType();
	    }
    }


    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            // store into the cookie only if the controller did not already change the value
            if ($this->newTheme == $this->activeTheme->getName()) {
                $cookie = new Cookie(
                    $this->cookieOptions['name'],
                    $this->newTheme,
                    time() + $this->cookieOptions['lifetime'],
                    $this->cookieOptions['path'],
                    $this->cookieOptions['domain'],
                    (Boolean)$this->cookieOptions['secure'],
                    (Boolean)$this->cookieOptions['http_only']
                );
                $event->getResponse()->headers->setCookie($cookie);
            }
        }
    }
}
