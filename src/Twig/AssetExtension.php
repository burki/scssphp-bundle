<?php declare(strict_types=1);
namespace Armin\ScssphpBundle\Twig;

use Armin\ScssphpBundle\Scss\Parser;
use Symfony\Component\Asset\Packages;

class AssetExtension extends \Symfony\Bridge\Twig\Extension\AssetExtension
{
    /**
     * @var Parser
     */
    private $scssParser;

    public function __construct(Packages $packages, Parser $scssParser)
    {
        parent::__construct($packages);
        $this->scssParser = $scssParser;
    }

    public function getAssetUrl($path, $packageName = null)
    {
        if ($this->scssParser->isEnabled() && $this->scssParser->isConfigured($path)) {
            return $this->scssParser->parse($path);
        }
        return parent::getAssetUrl($path, $packageName);
    }
}