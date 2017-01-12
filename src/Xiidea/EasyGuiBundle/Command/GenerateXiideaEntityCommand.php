<?php

namespace Xiidea\EasyGuiBundle\Command;

use Sensio\Bundle\GeneratorBundle\Command\GenerateDoctrineEntityCommand;
use Sensio\Bundle\GeneratorBundle\Command\Validators;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Xiidea\EasyGuiBundle\Generator\DoctrineEntityGenerator;

/**
 * Initializes a Doctrine entity inside a bundle.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class GenerateXiideaEntityCommand extends GenerateDoctrineEntityCommand
{
    protected function configure()
    {
        $this
            ->setName('xiidea:generate:entity')
            ->setAliases(array('generate:doctrine:entity'))
            ->setDescription('Generates a new Doctrine entity inside a bundle')
            ->addOption('entity', null, InputOption::VALUE_REQUIRED, 'The entity class name to initialize (shortcut notation)')
            ->addOption('fields', null, InputOption::VALUE_REQUIRED, 'The fields to create with the new entity')
            ->addOption('maps', null, InputOption::VALUE_OPTIONAL, 'Association mappting with other entity', array())
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Use the format for configuration files (php, xml, yml, or annotation)', 'annotation')
            ->setHelp(<<<EOT
The <info>%command.name%</info> task generates a new Doctrine
entity inside a bundle:

<info>php %command.full_name% --entity=AcmeBlogBundle:Blog/Post</info>

The above command would initialize a new entity in the following entity
namespace <info>Acme\BlogBundle\Entity\Blog\Post</info>.

You can also optionally specify the fields you want to generate in the new entity:

<info>php %command.full_name% --entity=AcmeBlogBundle:Blog/Post --fields="title:string(255) body:text"</info>

You can also optionally specify the association fields you want to generate in the new

<info>php %command.full_name% --entity=AcmeBlogBundle:Blog/Post --format=annotation --maps="entryBy:User"</info>

By default, the command uses annotations for the mapping information; change it with <comment>--format</comment>:

<info>php %command.full_name% --entity=AcmeBlogBundle:Blog/Post --format=yml</info>

To deactivate the interaction mode, simply use the <comment>--no-interaction</comment> option
without forgetting to pass all needed options:

<info>php %command.full_name% --entity=AcmeBlogBundle:Blog/Post --format=annotation --fields="title:string(255) body:text" --no-interaction</info>

This also has support for passing field specific attributes:

<info>php %command.full_name% --entity=AcmeBlogBundle:Blog/Post --format=annotation --fields="title:string(length=255 nullable=true unique=true) body:text ranking:decimal(precision:10 scale:0)" --no-interaction</info>
EOT
            );
    }

    /**
     * @throws \InvalidArgumentException When the bundle doesn't end with Bundle (Example: "Bundle/MySampleBundle")
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();

        $associations = $this->parseMaps($input->getOption('maps'));

        $entity = Validators::validateEntityName($input->getOption('entity'));
        list($bundle, $entity) = $this->parseShortcutNotation($entity);
        $format = Validators::validateFormat($input->getOption('format'));
        $fields = $this->parseFields($input->getOption('fields'));

        $questionHelper->writeSection($output, 'Entity generation');

        $bundle = $this->getContainer()->get('kernel')->getBundle($bundle);

        /** @var DoctrineEntityGenerator $generator */
        $generator = $this->getGenerator();
        $generatorResult = $generator->generate($bundle, $entity, $format, array_values($fields), array_values($associations));

        $output->writeln(sprintf(
            '> Generating entity class <info>%s</info>: <comment>OK!</comment>',
            $this->makePathRelative($generatorResult->getEntityPath())
        ));
        $output->writeln(sprintf(
            '> Generating repository class <info>%s</info>: <comment>OK!</comment>',
            $this->makePathRelative($generatorResult->getRepositoryPath())
        ));
        if ($generatorResult->getMappingPath()) {
            $output->writeln(sprintf(
                '> Generating mapping file <info>%s</info>: <comment>OK!</comment>',
                $this->makePathRelative($generatorResult->getMappingPath())
            ));
        }

        $questionHelper->writeGeneratorSummary($output, array());
    }


    private function parseFields($input)
    {
        if (is_array($input)) {
            return $input;
        }

        $fields = array();
        foreach (preg_split('{(?:\([^\(]*\))(*SKIP)(*F)|\s+}', $input) as $value) {
            $elements = explode(':', $value);
            $name = $elements[0];
            $fieldAttributes = array();
            if (strlen($name)) {
                $fieldAttributes['fieldName'] = $name;
                $type = isset($elements[1]) ? $elements[1] : 'string';
                preg_match_all('{(.*)\((.*)\)}', $type, $matches);
                $fieldAttributes['type'] = isset($matches[1][0]) ? $matches[1][0] : $type;
                $length = null;
                if ('string' === $fieldAttributes['type']) {
                    $fieldAttributes['length'] = $length;
                }
                if (isset($matches[2][0]) && $length = $matches[2][0]) {
                    $attributesFound = array();
                    if (false !== strpos($length, '=')) {
                        preg_match_all('{([^,= ]+)=([^,= ]+)}', $length, $result);
                        $attributesFound = array_combine($result[1], $result[2]);
                    } else {
                        $fieldAttributes['length'] = $length;
                    }
                    $fieldAttributes = array_merge($fieldAttributes, $attributesFound);
                    foreach (array('length', 'precision', 'scale') as $intAttribute) {
                        if (isset($fieldAttributes[$intAttribute])) {
                            $fieldAttributes[$intAttribute] = (int) $fieldAttributes[$intAttribute];
                        }
                    }
                    foreach (array('nullable', 'unique') as $boolAttribute) {
                        if (isset($fieldAttributes[$boolAttribute])) {
                            $fieldAttributes[$boolAttribute] = filter_var($fieldAttributes[$boolAttribute], FILTER_VALIDATE_BOOLEAN);
                        }
                    }
                }

                $fields[$name] = $fieldAttributes;
            }
        }

        return $fields;
    }

    protected function createGenerator()
    {
        return new DoctrineEntityGenerator($this->getContainer()->get('filesystem'), $this->getContainer()->get('doctrine'));
    }

    private function parseMaps($input)
    {
        if (is_array($input)) {
            return $input;
        }

        $maps = array();
        foreach (preg_split('{(?:\([^\(]*\))(*SKIP)(*F)|\s+}', $input) as $value) {
            $elements = explode(':', $value);
            $name = $elements[0];

            $fieldAttributes = array();
            if (strlen($name) || (isset($elements[1]) && strlen($elements[1]))) {
                $fieldAttributes['fieldName'] = $name;
                $targetEntity = $elements[1];
                preg_match_all('{(.*)\((.*)\)}', $targetEntity, $matches);
                $fieldAttributes['targetEntity'] = isset($matches[1][0]) ? $matches[1][0] : $targetEntity;
                $length = null;

                if (isset($matches[2][0]) && $length = $matches[2][0]) {
                    $attributesFound = array();

                    if (false !== strpos($length, '=')) {
                        preg_match_all('{([^,= ]+)=([^,= ]+)}', $length, $result);
                        $attributesFound = array_combine($result[1], $result[2]);
                    }

                    $fieldAttributes = array_merge($fieldAttributes, $attributesFound);
                }

                $maps[$name] = $fieldAttributes;
            }
        }

        return $maps;
    }
}