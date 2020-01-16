<?php
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
 *             @OA\Schema(type="string", enum={"active", "inactive"}, default="active"),
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
 *             description="Include data from related models in payload. Comma seperated list.",
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
 *     ),
 *     security={
 *         {"pm_api_auth_code": {}},
 *         {"pm_api_bearer": {}},
 *         {"pm_api_key": {}}
 *     },
 * )
 */