<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Migrations\Operations\Table;

use Spiral\Database\Entities\AbstractHandler;
use Spiral\Migrations\CapsuleInterface;
use Spiral\Migrations\Exceptions\Operations\TableException;
use Spiral\Migrations\Operations\TableOperation;

class DropTable extends TableOperation
{
    /**
     * {@inheritdoc}
     */
    public function execute(CapsuleInterface $capsule)
    {
        $schema = $capsule->getSchema($this->getTable(), $this->getDatabase());
        $database = $this->database ?? '[default]';

        if (!$schema->exists()) {
            throw new TableException(
                "Unable to drop table '{$database}'.'{$this->getTable()}', table does not exists"
            );
        }

        $schema->declareDropped();
        $schema->save(AbstractHandler::DO_ALL);
    }
}