<?php echo '<?php', PHP_EOL; ?>

namespace <?php echo $namespace; ?>;

use Tarantool\Mapper\Contracts\Migration;
use Tarantool\Mapper\Contracts\Manager;

class <?php echo $class; ?> implements Migration
{
    public function migrate(Manager $manager)
    {
    }
}