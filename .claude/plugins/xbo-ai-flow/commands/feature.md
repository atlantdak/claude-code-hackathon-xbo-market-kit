---
name: feature
description: Start the full AI development pipeline for a new feature. Usage: /feature [description]
---

You are starting the full AI development pipeline for a new feature.

**User's feature request:** $ARGUMENTS

Execute the orchestrate skill to run the complete pipeline:

1. Invoke the `xbo-ai-flow:orchestrate` skill via the Skill tool
2. Pass the user's feature description as context
3. Follow ALL steps in the orchestrate skill (record → brainstorm → plan → execute → verify → review → document)

If no arguments were provided, ask the user: "What feature would you like to implement?"
