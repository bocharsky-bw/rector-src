<?php

namespace Rector\Tests\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector\FixtureUnionTypes;

class MultipleTypes
{
    public function set()
    {
        $this->value = 5;

        $this->value = 'hey';

        $this->value = false;
    }
}

?>
-----
<?php

namespace Rector\Tests\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector\FixtureUnionTypes;

class MultipleTypes
{
    public bool|int|string $value;
    public function set()
    {
        $this->value = 5;

        $this->value = 'hey';

        $this->value = false;
    }
}

?>
