<?php

namespace Oro\Bundle\ThemeBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('oro_theme');
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                'theme_configuration' => ['type' => 'integer', 'value' => null]
            ]
        );

        $rootNode
            ->beforeNormalization()
                ->ifTrue(function ($value) {
                    if (!isset($value['themes'])) {
                        return false;
                    }

                    foreach ($value['themes'] as $themeName => $value) {
                        if (str_contains($themeName, '-') && !str_contains($themeName, '_')) {
                            return true;
                        }
                    }
                    return false;
                })
                ->thenInvalid("Theme name should not contain only '-' special characters")
            ->end()
            ->children()
                ->arrayNode('themes')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('label')
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('logo')
                            ->end()
                            ->scalarNode('icon')
                            ->end()
                            ->scalarNode('screenshot')
                            ->end()
                            ->booleanNode('rtl_support')
                                ->info('Defines whether Theme supports RTL and additional *.rtl.css have to be build')
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('active_theme')
                    ->cannotBeEmpty()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
