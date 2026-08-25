## Summary

- **MCP** is one standard shape for tools, so the same tool works in any app that speaks it.
- Without it, every app writes its own version of the same tool. Twelve tools in four apps is 48 things to maintain.
- A server offers three things: **tools** (do something), **resources** (read something), **prompts** (templates).
- MCP moves the wire format. It does **not** handle login, permissions or logging — those stay yours.
- If only one app will ever use a tool, a plain function is better.

## The problem

You built the support assistant. Then:

- The ops dashboard wants the same order lookup
- The Slack bot wants it too
- Claude Code should read it while you debug
- Next quarter there is a mobile app

That is four codebases, each with its own copy of the same tool, its own argument names, and its own idea of
what "order" means. Change one thing and you must update four places. You will forget one.

MCP solves that. It is plumbing, not intelligence.

## What MCP is

A simple message format (JSON-RPC) that lets any client ask your server three questions:

| It offers | The question it answers | Example |
|---|---|---|
| **Tools** | "What can you do?" | `lookup_order`, `create_ticket` |
| **Resources** | "What can I read?" | `handbook://returns-policy` |
| **Prompts** | "Any ready-made templates?" | `triage_ticket` |

One server, many clients:

```text
                   ┌──────────────────┐
 your Laravel app ─┤                  │
 Claude Code ──────┤   MCP server     ├── your database
 Slack bot ────────┤ (one codebase)   │
 mobile app ───────┤                  │
                   └──────────────────┘
```

The difference between a tool and a resource matters: a tool **does** something, a resource **is** something you
read. Reading a resource should be safe to repeat.

## What it looks like on the wire

```text
// 1. The client asks what exists.
{"jsonrpc":"2.0","id":1,"method":"tools/list"}

// 2. You answer with the same tool shape you already know.
{"jsonrpc":"2.0","id":1,"result":{"tools":[
  {"name":"lookup_order",
   "description":"Look up one order by reference. Returns status and dates.",
   "inputSchema":{"type":"object",
     "properties":{"reference":{"type":"string"}},"required":["reference"]}}
]}}

// 3. The client calls it.
{"jsonrpc":"2.0","id":2,"method":"tools/call",
 "params":{"name":"lookup_order","arguments":{"reference":"ORD-1043"}}}
```

Your handler is the same `match` you wrote in Level 1:

```php
public function callTool(string $name, array $arguments): array
{
    return match ($name) {
        // The customer comes from the token that reached this server.
        // NEVER from $arguments — no matter which protocol carries it.
        'lookup_order' => $this->lookupOrder(
            reference: $arguments['reference'],
            customer: $this->authenticatedCustomer(),
        ),
        default => ['error' => "Unknown tool: {$name}"],
    };
}
```

Use a ready-made SDK for the message handling. The value of MCP is the standard, not the parsing.

## Security does not change

- **Identity comes from the session or token**, exactly as in Level 1. A tool that accepts a `user_id` is just
  as wrong over MCP as it was over HTTP.
- **An MCP server on the internet needs login.** It is an API. Same rules.
- **Give each client only the tools it needs.** The Slack bot and the ops dashboard are not the same thing.
- **Tool results reach your prompt.** If a result contains "ignore your instructions", that is prompt
  injection, whether it came from your database or somebody else's server.
- **Log every call with the caller.** You will need it one day.

## Using somebody else's server

Now one integration gives you their whole toolset. Two things to think about first:

- **You did not write those tools.** Read what they do before letting an agent run them. "Filesystem access" is
  a phrase that should slow you down.
- **Their descriptions become part of your prompt.** A badly written description makes your agent behave badly,
  and you cannot fix it in your own code.

## When not to use it

**Skip MCP** when one app uses the tool and nothing else ever will. A plain function is less code and easier to
debug.

**Use MCP** when two or more apps need the same tool, when you want Claude Code or a desktop app to reach your
systems, or when another team will use your tools.

## Common mistakes

- **Wrapping everything in MCP because it is new.** One consumer does not need a protocol.
- **Trusting a third-party server without reading its tools.** You are handing an agent somebody else's verbs.
- **Thinking MCP gives you permissions.** It gives you transport and discovery. Nothing else.
- **Vague descriptions.** Still the whole interface, and now several apps depend on it.
- **Changing an argument name.** You just broke clients you cannot see. Version your tools.

## You should now be able to

- [ ] Explain MCP as one standard shape for tools, resources and prompts
- [ ] Say when a plain function is the better answer
- [ ] Tell a tool apart from a resource
- [ ] List what MCP does not give you: login, permissions, logging
- [ ] Decide safely whether to switch on somebody else's server

## Practice

1. Count how many places in your company define the same tool. That number is your reason to use MCP.
2. Take one read-only tool and expose it over MCP. Call it from a second app.
3. Read the tool list of a public MCP server. Would you let an agent run them?
4. Write down where identity comes from in your server. If the answer is "an argument", fix it.
