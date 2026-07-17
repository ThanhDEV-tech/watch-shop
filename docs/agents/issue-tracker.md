# Issue tracker: Local Markdown

Issues, specs, audit reports, and grill sessions for this repo live as markdown files in `.agents/docs/`.

This is a personal project. Do not use GitHub Issues, GitLab Issues, Jira, Linear, or any other issue tracker for skill output unless the user explicitly asks to switch later.

## Conventions

- One effort per directory: `.agents/docs/<feature-slug>/`
- The spec is `.agents/docs/<feature-slug>/spec.md`
- Audit reports live under `.agents/docs/<feature-slug>/audit-report.md`
- Grill sessions live under `.agents/docs/<feature-slug>/grill-session.md`
- Implementation issues are one file per ticket at `.agents/docs/<feature-slug>/issues/<NN>-<slug>.md`, numbered from `01`; never use a single combined tickets file
- Triage or workflow state, when needed, is recorded as a `Status:` line near the top of each issue file
- Comments and conversation history append to the bottom of the file under a `## Comments` heading

## When a skill says "publish to the issue tracker"

Create a new markdown file under `.agents/docs/<feature-slug>/`, creating the directory if needed.

## When a skill asks where to store output

Choose local markdown files under `.agents/docs/`. Do not use GitHub Issues or any external tracker.

## When a skill says "fetch the relevant ticket"

Read the file at the referenced local path. The user will normally pass the path or the issue number directly.

## Wayfinding operations

Used by `/wayfinder`. The map is a file with one child file per ticket.

- **Map**: `.agents/docs/<effort>/map.md` - the Notes / Decisions-so-far / Fog body.
- **Child ticket**: `.agents/docs/<effort>/issues/NN-<slug>.md`, numbered from `01`, with the question in the body. A `Type:` line records the ticket type (`research`/`prototype`/`grilling`/`task`); a `Status:` line records `claimed`/`resolved`.
- **Blocking**: a `Blocked by: NN, NN` line near the top. A ticket is unblocked when every file it lists is `resolved`.
- **Frontier**: scan `.agents/docs/<effort>/issues/` for files that are open, unblocked, and unclaimed; first by number wins.
- **Claim**: set `Status: claimed` and save before any work.
- **Resolve**: append the answer under an `## Answer` heading, set `Status: resolved`, then append a context pointer (gist + link) to the map's Decisions-so-far in `map.md`.
