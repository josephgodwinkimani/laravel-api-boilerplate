<?php

declare(strict_types=1);

/*
 * This file is part of the laravel-api-boilerplate project.
 *
 * (c) Joseph Godwin Kimani <josephgodwinkimani@gmx.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Services;

use App\Serializers\XmlSerializer;
use App\Transformers\UserTransformer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use League\Fractal\Manager;
use League\Fractal\Pagination\CursorInterface;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Serializer\DataArraySerializer;
use League\Fractal\Serializer\JsonApiSerializer;
use League\Fractal\Serializer\ArraySerializer;
use League\Fractal\Resource\ResourceInterface;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use SimpleXMLElement;
use Symfony\Component\Yaml\Yaml;

/**
 * Class DataTransformService.
 */
class DataTransformService
{
    protected $fractal;

    public function __construct()
    {
        $this->fractal = new Manager();
    }

    /**
     * Transform an Item
     *
     * @param string $resourceKey
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param \League\Fractal\TransformerAbstract $transformer
     * @param string $type
     * @return array|bool|string|null
     */
    public function item(string $resourceKey, Model $model, TransformerAbstract $transformer, string $type = null)
    {
        /**
         * Structure all Transformed data in certain ways
         */
        if (is_null($type)) {
            /**
             * A representation of the JSON-API standard (v1.0)
             */
            $this->fractal->setSerializer(new JsonApiSerializer());
        }

        if ($type === "data") {
            /**
             * Adds the 'data' namespace
             */
            $this->fractal->setSerializer(new DataArraySerializer());
        }

        if ($type === "array" || $type === "yaml" || $type === "text") {
            /**
             * Remove the 'data' namespace for result items
             */
            $this->fractal->setSerializer(new ArraySerializer());
        }

        // Make a resource out of the data from ORM call
        $resource = new Item($model, $transformer, $resourceKey);

        // Make a resource then pass it over, and use toArray()
        $data = $this->fractal->createData($resource)->toArray();

        /**
         * Return results in Extensible Markup Language format
         */
        if ($type === "xml") {
            $xml = new SimpleXMLElement('<?xml version="1.0"?><data></data>');
            $this->array_to_xml($data, $xml);
            return $xml->asXML();
        }

        /**
         * Return results in YAML Ain't Markup Language format
         *
         * YAML (YAML Ain't Markup Language) is a human-readable data serialization format used for configuration files, data exchange, and more.
         */
        if ($type === "yaml") {
            return Yaml::dump($data);
        }

        /**
         * Return results in Text format
         */
        if ($type === "text") {
            return implode(', ', $data);
        }

        return $data;
    }

    /**
     * Transform a Collection
     *
     * @param string $resourceKey
     * @param \Illuminate\Database\Eloquent\Collection $collection
     * @param \League\Fractal\TransformerAbstract $transformer
     * @param string $type
     * @param \Illuminate\Contracts\Pagination\LengthAwarePaginator|null $paginator
     * @param \League\Fractal\Pagination\CursorInterface|null $cursor
     * @return array|bool|string|null
     */
    public function collection(string $resourceKey, \Illuminate\Database\Eloquent\Collection $collection, TransformerAbstract $transformer, string $type = null, LengthAwarePaginator $paginator = null, CursorInterface $cursor = null)
    {
        /**
         * Structure all Transformed data in certain ways
         */
        if (is_null($type)) {
            /**
             * A representation of the JSON-API standard (v1.0)
             */
            $this->fractal->setSerializer(new JsonApiSerializer());
        }

        if ($type === "data") {
            /**
             * Adds the 'data' namespace
             */
            $this->fractal->setSerializer(new DataArraySerializer());
        }

        if ($type === "array" || $type === "yaml" || $type === "text") {
            /**
             * Remove the 'data' namespace for result items
             */
            $this->fractal->setSerializer(new ArraySerializer());
        }

        /**
         * Make a resource out of the data from ORM call
         *
         * Resource Collection - The data can be a collection of any sort of data, as long as the
         * "collection" is either array or an object implementing ArrayIterator.
         */
        $resource = new Collection($collection, $transformer, $resourceKey);

        /**
         * Offer more information about your result-set including total, and have next/previous links which will only show if there is more data available
         */
        if (!is_null($paginator)) {
            $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));
        }

        /**
         * Use cursor that will indicate where to start fetching results
         */
        if (!is_null($cursor)) {
            $resource->setCursor($cursor);
        }

        /*
         * Make a resource then pass it over, and use toArray()
         */
        $data = $this->fractal->createData($resource)->toArray();

        /**
         * Return results in Extensible Markup Language format
         */
        if ($type === "xml") {
            $xml = new SimpleXMLElement('<?xml version="1.0"?><data></data>');
            $this->array_to_xml($data, $xml);
            return $xml->asXML();
        }

        /**
         * Return results in YAML Ain't Markup Language format
         *
         * YAML (YAML Ain't Markup Language) is a human-readable data serialization format used for configuration files, data exchange, and more.
         */
        if ($type === "yaml") {
            return Yaml::dump($data);
        }

        /**
         * Return results in Text format
         */
        if ($type === "text") {
            return implode(', ', $data);
        }

        return $data;
    }

    /**
     * Convert array to xml
     *
     * @param mixed $data
     * @param mixed $xml_data
     * @return void
     */
    private function array_to_xml($data, &$xml_data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item' . $key;
                }
                $subnode = $xml_data->addChild($key);
                $this->array_to_xml($value, $subnode);
            } else {
                $xml_data->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }

}