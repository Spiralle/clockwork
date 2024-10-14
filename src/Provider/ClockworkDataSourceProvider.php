<?php declare(strict_types = 1);

namespace Spiralle\Clockwork\Provider;

use Clockwork\DataSource\DataSourceInterface;

interface ClockworkDataSourceProvider
{

	public function provide(): DataSourceInterface;

}
