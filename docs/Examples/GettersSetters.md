Getters and Setters
===================
By default, the getter and setter functions for entity properties are the camelised version of the property name:

    /**
     * @Orm\Entity
     */
    class Entity
    {
        /**
         * @var string
         * @Orm\Column(type="string")
         */
        protected $name;

        /**
         * Get name
         *
         * @return string
         */
        public function getName()
        {
            return $this->name;
        }
    
        /**
         * Set name
         *
         * @param string $name
         * @return $this
         */
        public function setName($name)
        {
            $this->name = $name;
            return $this;
        }
    }
    
However this behaviour is standard, not enforced. You can alter the name of your getter and setter functions easily:

    /**
     * @Orm\Entity
     */
    class Entity
    {
        /**
         * @var string
         * @Orm\Column(type="string", getter="get_name", setter="setEntityName")
         */
        protected $name;

        /**
         * Get name
         *
         * @return string
         */
        public function get_name()
        {
            return $this->name ?: "default name";
        }
    
        /**
         * Set name
         *
         * @param string $name
         * @return $this
         */
        public function setEntityName($name)
        {
            $this->name = (string)$name;
            return $this;
        }
    }

Setters must always take a single argument containing the value to apply to the field. There is nothing wrong with
manipulating the value, as long as the getter and setter always set and return the value as per the @Column 
specification.
