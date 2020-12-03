<?php
// Test this using following command
// php -S localhost:8080 ./graphql.php &
// curl http://localhost:8080 -d '{"query": "query { echo(message: \"Hello World\") }" }'
// curl http://localhost:8080 -d '{"query": "mutation { sum(x: 2, y: 2) }" }'
require_once __DIR__ . '/../vendor/autoload.php';

require_once("config.php");
require_once("telegram_functions.php");

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\GraphQL;
use \Firebase\JWT\JWT;

try {
    $queryType = new ObjectType([
        'name' => 'Query',
        'fields' => [
            'echo' => [
                'type' => Type::string(),
                'args' => [
                    'message' => ['type' => Type::string()],
                ],
                'resolve' => function ($root, $args) {
                    return $root['prefix'] . $args['message'];
                }
            ],
        ],
    ]);
    $mutationType = new ObjectType([
        'name' => 'Mutation',
        'fields' => [
			'say' => [
                'type' => Type::string(),
                'args' => [
                    'message' => ['type' => Type::string()],
					'external_id' => ['type' => Type::string()],
					'external_type' => ['type' => Type::string()],
                ],
                'resolve' => function ($root, $args) {
                    global $bot_token;
                    global $ecosystem_server_secret;
                    $token = trim(explode(" ", getallheaders()["Authorization"])[1]);
                    $decoded = JWT::decode($token, $ecosystem_server_secret, array('HS256'));
                    
                    answer($args["message"], $args["external_id"]);
                }
            ],
        ],
    ]);
    // See docs on schema options:
    // http://webonyx.github.io/graphql-php/type-system/schema/#configuration-options
    $schema = new Schema([
        'query' => $queryType,
        'mutation' => $mutationType,
    ]);
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    $query = $input['query'];
    $variableValues = isset($input['variables']) ? $input['variables'] : null;
    $rootValue = ['prefix' => 'You said: '];
    $result = GraphQL::executeQuery($schema, $query, $rootValue, null, $variableValues);
    $output = $result->toArray();
} catch (\Exception $e) {
    $output = [
        'error' => [
            'message' => $e->getMessage()
        ]
    ];
}
header('Content-Type: application/json; charset=UTF-8');
echo json_encode($output);
