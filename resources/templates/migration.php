<?php echo '<?php', PHP_EOL; ?>

namespace <?php echo $namespace; ?>;

use Tarantool\Mapper\Migration;
use Tarantool\Mapper\Mapper;

class <?php echo $class; ?> implements Migration
{
    public function migrate(Mapper $mapper)
    {
    }
}