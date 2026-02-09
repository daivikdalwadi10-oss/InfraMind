# Demo Script (5-7 minutes)

## 1) Intro (30s)
- Problem: structured infrastructure analysis and review workflow
- Solution: InfraMind combines role-based workflows with AI-assisted analysis

## 2) Landing and Auth (60s)
- Show landing page, value proposition, and credibility section
- Log in as Manager and Employee

## 3) Manager Flow (2 min)
- Create a team (optional) and assign an employee
- Create a task and assign it to the employee

## 4) Employee Flow (2 min)
- Open assigned task
- Create analysis (type: LATENCY)
- Fill symptoms, signals, and readiness score >= 75
- Submit analysis

## 5) Review + Report (1 min)
- Manager reviews and approves analysis
- Show reports list or status change

## 6) Wrap (30s)
- Highlight glass UI, dark mode, and AI assistance
- Mention extensibility and audit history

## Notes
- Backend: http://localhost:8000
- Frontend: http://localhost:3000
- AI calls are server-side only (Genkit) and require `GENKIT_API_KEY`
