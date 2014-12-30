<?php
namespace Bravo3\Orm\Traits;

interface CreateModifyInterface
{
    /**
     * Get the time the entity was created
     *
     * @return \DateTime
     */
    public function getTimeCreated();

    /**
     * Set the time the entity was created
     *
     * @param \DateTime $time_created
     * @return $this
     */
    public function setTimeCreated(\DateTime $time_created);


    /**
     * Get the time the entity was last modified
     *
     * @return \DateTime
     */
    public function getLastModified();

    /**
     * Set the time the entity was last modified
     *
     * @param \DateTime $last_modified
     * @return $this
     */
    public function setLastModified(\DateTime $last_modified);
}