<?php echo '<?php', PHP_EOL; ?>

namespace Migration\<?php echo $namespace; ?>;

use Tarantool\Mapper\Migration;
use Tarantool\Mapper\Mapper;

class <?php echo $class; ?> implements Migration
{
    public $created_at = '<?php echo $created_at; ?>';

    public function migrate(Mapper $mapper)
    {
        throw new \Exception("Not implemented yet!");
    }
}
