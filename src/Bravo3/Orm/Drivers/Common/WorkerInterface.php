<?php
namespace Bravo3\Orm\Drivers\Common;

interface WorkerInterface
{
    /**
     * Execute the command
     *
     * @param array $parameters
     * @return mixed
     */
    public function execute(array $parameters);

    /**
     * Returns a list of required parameters
     *
     * @return string[]
     */
    public function getRequiredParameters();
}
