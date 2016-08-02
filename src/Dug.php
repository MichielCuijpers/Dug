<?php


namespace Dug;

use Dug\Business\DataCombiner;
use Dug\Business\ReferenceResolver;
use Dug\Business\RouteMatcher;
use Dug\Exceptions\RouteNotFoundException;
use Dug\Objects\Data;
use Dug\Objects\Source;

class Dug
{

    /**
     * @var Source[]
     */
    private $sources = [];

    /**
     * @param Source $source
     */
    public function register(Source $source)
    {
        $this->sources[] = $source;
    }

    /**
     * @param array $path
     * @return mixed
     */
    public function fetch(array $path)
    {
        $data = $this->data($path);

        return $this->dataToArray($data);
    }

    /**
     * @param $path
     * @return mixed|null
     */
    public function fetchSingle($path)
    {
        $result = $this->fetch($path);

        return isset($result[0]) ? $result[0] : null;
    }


    /**
     * @param array $path
     * @return Data[]
     */
    public function data(array $path)
    {
        $source = $this->sourceForPath($path);
        if ($source === null) {
            throw new RouteNotFoundException($path);
        }

        $combined = DataCombiner::combine($source->getCallback()->call($source, $path));

        return (new ReferenceResolver($this))->process($combined);
    }

    /**
     * @param array $path
     * @return Source|null
     */
    public function sourceForPath(array $path)
    {
        foreach ($this->sources as $source) {
            if (RouteMatcher::matches($source, $path)) {
                return $source;
            }
        }

        return null;
    }

    /**
     * @param mixed $data
     * @return mixed
     */
    private function dataToArray($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $item) {
                $data[$key] = $this->dataToArray($item);
            }
        }
        if ($data instanceof Data) {
            $data = $this->dataToArray($data->getValue());
        }

        return $data;
    }

}