<?php

return [
    'id' => 'mcp-shape',
    'module' => 'mcp',
    'title' => 'What an MCP conversation actually looks like on the wire',
    'intro' => 'MCP sounds abstract until you see the messages. It is JSON-RPC: the client asks what exists, then calls one of the things. Your handler body is the same match statement you already wrote in Level 1.',
    'language' => 'json',
    'code' => <<<'JSON'
    // 1. The client asks what this server can do.
    {"jsonrpc":"2.0","id":1,"method":"tools/list"}

    // 2. You answer with definitions — the same shape as any tool schema.
    {"jsonrpc":"2.0","id":1,"result":{"tools":[
      {"name":"lookup_order",
       "description":"Look up one order by reference. Returns status, totals and delivery dates. Returns found:false when no such order exists — do not retry with a guessed reference.",
       "inputSchema":{"type":"object",
         "properties":{"reference":{"type":"string","description":"e.g. ORD-1043"}},
         "required":["reference"]}}
    ]}}

    // 3. The client calls one.
    {"jsonrpc":"2.0","id":2,"method":"tools/call",
     "params":{"name":"lookup_order","arguments":{"reference":"ORD-1043"}}}

    // 4. You run YOUR code and return the result.
    {"jsonrpc":"2.0","id":2,"result":{"content":[
      {"type":"text","text":"{\"found\":true,\"status\":\"shipped\",\"expected_on\":\"2026-08-22\"}"}
    ]}}
    JSON,
    'output_language' => 'php',
    'output' => <<<'PHP'
    // The handler. Nothing new — and note what is missing from $arguments.
    public function callTool(string $name, array $arguments): array
    {
        return match ($name) {
            // Identity comes from the session or token that reached this
            // server. Never from $arguments, no matter what protocol carries it.
            'lookup_order' => $this->lookupOrder(
                reference: $arguments['reference'],
                customer: $this->authenticatedCustomer(),
            ),

            'create_ticket' => $this->createTicket($arguments, $this->authenticatedCustomer()),

            default => ['error' => "Unknown tool: {$name}"],
        };
    }
    PHP,
    'notes' => [
        'The value of MCP is the standard, not the parsing. Use a maintained SDK for the transport and spend your effort on the tool bodies.',
        'A tool that accepts a <code>user_id</code> argument is exactly as wrong over MCP as it was over HTTP. The protocol moves the wire format, not the responsibility.',
        'Because several clients now depend on these descriptions, changing an argument name is a breaking change for consumers you cannot see. Version them.',
    ],
    'live' => null,
];
