@echo off
cd /d C:\xampp\htdocs\ai-job-portal
C:\xampp\php\php.exe spark jobs:scrape-external --limit 100 --sources remotive,remoteok,arbeitnow
