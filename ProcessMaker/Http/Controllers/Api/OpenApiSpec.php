<?php

namespace ProcessMaker\Http\Controllers\Api;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         version="1.0.0",
 *         title="ProcessMaker API",
 *         description="",
 *         @OA\Contact(
 *             email="info@processmaker.com"
 *         ),
 *         @OA\License(
 *             name="Apache 2.0",
 *             url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *         )
 *     ),
 *     @OA\Components(
 *         @OA\Parameter(
 *             name="filter",
 *             in="query",
 *             description="Filter results by string. Searches Name, Description, and Status. Status must match exactly. Others can be a substring.",
 *             @OA\Schema(type="string"),
 *         ),
 *         @OA\Parameter(
 *             parameter="order_by",
 *             name="order_by",
 *             in="query",
 *             description="Field to order results by",
 *             @OA\Schema(type="string"),
 *         ),
 *         @OA\Parameter(
 *             parameter="status",
 *             name="status",
 *             in="query",
 *             description="ACTIVE or INACTIVE",
 *             @OA\Schema(type="string", enum={"ACTIVE", "INACTIVE"}),
 *         ),
 *         @OA\Parameter(
 *             parameter="order_direction",
 *             name="order_direction",
 *             in="query",
 *             @OA\Schema(type="string", enum={"asc", "desc"}, default="asc"),
 *         ),
 *         @OA\Parameter(
 *             parameter="per_page",
 *             name="per_page",
 *             in="query",
 *             @OA\Schema(type="integer", default="10"),
 *         ),
 *         @OA\Parameter(
 *             parameter="include",
 *             name="include",
 *             in="query",
 *             description="Include data from related models in payload. Comma separated list.",
 *             @OA\Schema(type="string", default=""),
 *         ),
 *         @OA\Parameter(
 *             parameter="member_id",
 *             name="member_id",
 *             in="query",
 *             @OA\Schema(type="integer"),
 *         ),
 *         @OA\Parameter(
 *             parameter="commentable_id",
 *             name="commentable_id",
 *             in="query",
 *             @OA\Schema(type="integer"),
 *         ),
 *         @OA\Parameter(
 *             parameter="commentable_type",
 *             name="commentable_type",
 *             in="query",
 *             @OA\Schema(type="string", default=""),
 *         ),
 *         @OA\Schema(
 *           schema="DateTime",
 *           @OA\Property(property="date", type="string"),
 *         ),
 *         @OA\Response(
 *           response=404,
 *           description="Not Found",
 *           @OA\JsonContent(@OA\Property(property="error", type="string"))
 *         ),
 *         @OA\Response(
 *           response=422,
 *           description="Unprocessable Entity",
 *           @OA\JsonContent(
 *              @OA\Property(property="message", type="string"),
 *              @OA\Property(property="errors", type="object"),
 *           )
 *         ),
 *     ),
 * )
 */
class OpenApiSpec
{
}
