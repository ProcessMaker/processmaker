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
 *     @OA\Server(url="/api/1.0"),
 *     @OA\Components(
 *         @OA\Parameter(
 *             parameter="filter",
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
 *     ),
 *     security={{"pm_api_bearer": {}}},
 * )
 */