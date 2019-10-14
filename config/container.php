<?php
declare(strict_types=1);

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\ResolveEnvPlaceholdersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

$containerBuilder = new ContainerBuilder();
$loader = new XmlFileLoader($containerBuilder, new FileLocator(__DIR__));
$loader->load('services.xml');

$containerBuilder->setParameter('root_dir', dirname(__DIR__, 1));
$containerBuilder->addCompilerPass(new ResolveEnvPlaceholdersPass());

$containerBuilder->compile();
return $containerBuilder;
