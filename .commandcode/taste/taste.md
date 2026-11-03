# Taste

- When auditing or investigating data loss/safety, conclusions must be evidence-based; do not treat absence of evidence in source code as proof that data is safe. Confidence: 0.9
- During forensic/read-only data-loss investigations, do not execute tests, migrations, seeds, wipes, or any data-changing commands; investigate read-only. Confidence: 0.9
- Do not attribute execution of destructive commands to the user or assistant without concrete evidence; state when executor identity cannot be determined. Confidence: 0.9