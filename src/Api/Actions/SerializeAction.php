<?php namespace Flarum\Api\Actions;

use Flarum\Api\Request;
use Flarum\Api\JsonApiRequest;
use Flarum\Api\JsonApiResponse;
use Tobscure\JsonApi\SerializerInterface;
use Tobscure\JsonApi\Criteria;

abstract class SerializeAction implements ActionInterface
{
    /**
     * The name of the serializer class to output results with.
     *
     * @var string
     */
    public static $serializer;

    /**
     * The relationships that are available to be included (keys), and which
     * ones are included by default (boolean values).
     *
     * @var array
     */
    public static $include = [];

    /**
     * The relationships that are linked by default.
     *
     * @var array
     */
    public static $link = [];

    /**
     * The maximum number of records that can be requested.
     *
     * @var integer
     */
    public static $limitMax = 50;

    /**
     * The number of records included by default.
     *
     * @var integer
     */
    public static $limit = 20;

    /**
     * The fields that are available to be sorted by.
     *
     * @var array
     */
    public static $sortFields = [];

    /**
     * The default sort field and order to user.
     *
     * @var string
     */
    public static $sort;

    /**
     * Handle an API request and return an API response.
     *
     * @param Flarum\Api\Request $request
     * @return Flarum\Api\Response
     */
    public function handle(Request $request)
    {
        $request = static::buildJsonApiRequest($request);

        $data = $this->data($request, $response = new JsonApiResponse);

        $serializer = new static::$serializer($request->actor, $request->include, $request->link);

        $response->content->setData($this->serialize($serializer, $data));

        return $response;
    }

    /**
     * Get the data to be serialized and assigned to the response document.
     *
     * @param Flarum\Api\JsonApiRequest $request
     * @param Flarum\Api\JsonApiResponse $response
     * @return array
     */
    abstract protected function data(JsonApiRequest $request, JsonApiResponse $response);

    /**
     * Serialize the data as appropriate.
     *
     * @param \Tobscure\JsonApi\SerializerInterface $serializer
     * @param array $data
     * @return \Tobscure\JsonApi\Elements\ElementInterface
     */
    abstract protected function serialize(SerializerInterface $serializer, $data);

    /**
     * Extract parameters from the request input and assign them to the
     * request, restricted by the action's specifications.
     *
     * @param Flarum\Api\Request $request
     * @return void
     */
    protected static function buildJsonApiRequest(Request $request)
    {
        $request = new JsonApiRequest($request->input, $request->actor, $request->http);

        $criteria = new Criteria($request->input);

        $request->include = static::sanitizeInclude($criteria->getInclude());
        $request->sort = static::sanitizeSort($criteria->getSort());
        $request->offset = $criteria->getOffset();
        $request->limit = static::sanitizeLimit($criteria->getLimit());
        $request->link = static::$link;

        return $request;
    }

    /**
     * Sanitize an array of included relationships according to the action's
     * configuration.
     *
     * @param array $include
     * @return array
     */
    protected static function sanitizeInclude(array $include)
    {
        return array_intersect($include, array_keys(static::$include)) ?: array_keys(array_filter(static::$include));
    }

    /**
     * Sanitize an array of sort criteria according to the action's
     * configuration.
     *
     * @param array $sort
     * @return array
     */
    protected static function sanitizeSort(array $sort)
    {
        return array_intersect_key($sort, array_flip(static::$sortFields)) ?: static::$sort;
    }

    /**
     * Sanitize a limit according to the action's configuration.
     *
     * @param int $limit
     * @return int
     */
    protected static function sanitizeLimit($limit)
    {
        return min($limit, static::$limitMax) ?: static::$limit;
    }
}