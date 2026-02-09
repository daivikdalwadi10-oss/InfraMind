# Submission Checklist

## Project Info
- Name, short tagline, and category entered
- Team members listed
- Repository URL provided
- License confirmed (if required)

## Build + Run
- Backend: `cd backend && composer install`
- Backend env: copy `backend/.env.example` to `backend/.env`
- Database: `cd backend && php bin/migrate.php && php bin/seed.php`
- Backend start: `cd backend && php -S localhost:8000 -t public router.php`
- Frontend: `cd frontend && npm install`
- Frontend env: copy `frontend/.env.local.example` to `frontend/.env.local`
- Frontend start: `cd frontend && npm run dev`

## Quality Gates
- Frontend lint: `cd frontend && npm run lint`
- Frontend typecheck: `cd frontend && npm run typecheck`
- Backend lint: `cd backend && composer lint`
- Backend tests: `cd backend && composer test`

## Demo Assets
- Demo script ready
- Release notes written
- Screenshots or short video captured (if required)

## Submission Notes
- Include any setup caveats (ports, env vars)
- Note AI features require `GENKIT_API_KEY` for live calls
