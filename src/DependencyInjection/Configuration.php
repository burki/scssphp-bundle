<?php declare(strict_types=1);
namespace Armin\ScssphpBundle\DependencyInjection;

use ScssPhp\ScssPhp\Formatter\Compact;
use ScssPhp\ScssPhp\Formatter\Compressed;
use ScssPhp\ScssPhp\Formatter\Crunched;
use ScssPhp\ScssPhp\Formatter\Expanded;
use ScssPhp\ScssPhp\Formatter\Nested;
use ScssPhp\ScssPhp\OutputStyle;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('scssphp');

        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('enabled')
                    ->defaultTrue()
                    ->info('When disabled, ScssPHP will not compile SCSS sources automatically, ' .
                        'by user\'s request. Compiling with CLI tool will still work.')
                ->end()
                ->booleanNode('autoUpdate')
                    ->defaultTrue()
                    ->info('Automatically re-compile SCSS sources on updates, when enabled.')
                ->end()
                ->arrayNode('assets')
                    ->info('List of SCSS assets, which should be compiled, when requested. Key is the asset name/path.')
                    ->useAttributeAsKey('name')
                    ->normalizeKeys(false)
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('src')
                                ->isRequired()
                                ->info('Path to SCSS source file (entrypoint), ' .
                                    'relative to Symfony\'s project directory.')
                            ->end()
                            ->scalarNode('outputFolder')
                                ->defaultValue('public')
                                ->info('Prepends this outputFolder to asset\'s destination path.')
                            ->end()
                            ->arrayNode('importPaths')
                                ->info('Array of import paths, relative to Symfony\'s project directory.')
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode('variables')
                                ->info('Array of scss variables, which should be used during compilation. ' .
                                    'Use key => value here.')
                                ->useAttributeAsKey('name')
                                ->scalarPrototype()->end()
                            ->end()
                            ->enumNode('outputStyle')
                                ->info('Expanded or compressed CSS output.')
                                ->values([
                                    OutputStyle::EXPANDED,
                                    OutputStyle::COMPRESSED,
                                ])
                                ->defaultValue(OutputStyle::COMPRESSED)
                                ->beforeNormalization()
                                    ->ifInArray([Expanded::class, Nested::class, Compressed::class, Compact::class, Crunched::class])
                                    ->then(function($v) { return OutputStyle::COMPRESSED; })
                                ->end()
                            ->end()
                            ->booleanNode('sourceMap')
                                ->info('When enabled, the CSS file contains inline sourceMap comments.')
                                ->defaultFalse()
                            ->end()
                            ->booleanNode('appendTimestamp')
                                ->info('When enabled, the timestamp of the asset, will be appended as query string.')
                                ->defaultTrue()
                            ->end()
                        ->end()
                    ->end() // prototype
                ->end() // /assets
            ->end();

        return $treeBuilder;
    }
}
