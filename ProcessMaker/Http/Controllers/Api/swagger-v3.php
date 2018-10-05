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
 *     @OA\Server(url=API_HOST),
 *     @OA\Components(
 *         @OA\SecurityScheme(
 *             securityScheme="pm-api",
 *             type="http",
 *             scheme="bearer",
 *         ),
 *         @OA\Schema(
 *           schema="metadata",
 *           @OA\Property(property="per_page", type="integer"),
 *         )
 *     ),
 *     security={{"pm-api": {}}},
 * )
 */