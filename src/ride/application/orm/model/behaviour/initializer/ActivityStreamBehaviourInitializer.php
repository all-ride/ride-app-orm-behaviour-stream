<?php

namespace ride\application\orm\model\behaviour\initializer;

use ride\application\orm\model\behaviour\ActivityStreamBehaviour;
use ride\library\generator\CodeClass;
use ride\library\generator\CodeGenerator;
use ride\library\orm\definition\field\BelongsToField;
use ride\library\orm\definition\ModelTable;
use ride\library\orm\model\behaviour\initializer\BehaviourInitializer;

/**
 * Initializer for the activity stream behaviour
 */
class ActivityStreamBehaviourInitializer implements BehaviourInitializer {

    /**
     * Gets the behaviours for the model of the provided model table
     * @param \ride\library\orm\definition\ModelTable $modelTable
     * @return array An array with instances of Behaviour
     * @see \ride\library\orm\model\behaviour\Behaviour
     */
    public function getBehavioursForModel(ModelTable $modelTable) {
        if (!$modelTable->getOption('behaviour.stream')) {
            return array();
        }

        if (!$modelTable->hasField('streamActivity')) {
            $activityStreamField = new BelongsToField('streamActivity', 'StreamActivity');
            $activityStreamField->setIsDependant(true);
            $activityStreamField->setOptions(array(
               'scaffold.form.omit' => true,
            ));

            $modelTable->addField($activityStreamField);
        }


        return array(new ActivityStreamBehaviour());
    }

    /**
     * Generates the needed code for the entry class of the provided model table
     * @param \ride\library\orm\definition\ModelTable $table
     * @param \ride\library\generator\CodeGenerator $generator
     * @param \ride\library\generator\CodeClass $class
     * @return null
     */
    public function generateEntryClass(ModelTable $modelTable, CodeGenerator $generator, CodeClass $class) {
        if (!$modelTable->getOption('behaviour.stream')) {
            return;
        }

        $class->addImplements('ride\\application\\orm\\entry\\StreamedActivityEntry');

        $isStreamedActivityMethod = $generator->createMethod('isStreamedActivity', array(), 'return true;');
        $isStreamedActivityMethod->setDescription('Checks whether this entry should be included in the stream');
        $isStreamedActivityMethod->setReturnValue($generator->createVariable('result', 'boolean'));

        $streamActivityArgument = $generator->createVariable('streamActivity', 'ride\\application\\orm\\entry\\StreamActivityEntry');

        $populateStreamActivityMethod = $generator->createMethod('populateStreamActivity', array($streamActivityArgument), '');
        $populateStreamActivityMethod->setDescription('Populates the custom fields on a stream activity');

        $class->addMethod($isStreamedActivityMethod);
        $class->addMethod($populateStreamActivityMethod);
    }

}
