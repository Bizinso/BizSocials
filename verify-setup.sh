#!/bin/bash

# BizSocials Setup Verification Script
# This script verifies that your Docker environment is properly configured

echo "🔍 BizSocials Setup Verification"
echo "================================"
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check Docker
echo "1. Checking Docker..."
if command -v docker &> /dev/null; then
    echo -e "${GREEN}✓${NC} Docker is installed"
    docker --version
else
    echo -e "${RED}✗${NC} Docker is not installed"
    exit 1
fi
echo ""

# Check Docker Compose
echo "2. Checking Docker Compose..."
if docker compose version &> /dev/null; then
    echo -e "${GREEN}✓${NC} Docker Compose is available"
    docker compose version
else
    echo -e "${RED}✗${NC} Docker Compose is not available"
    exit 1
fi
echo ""

# Check if Docker is running
echo "3. Checking if Docker is running..."
if docker info &> /dev/null; then
    echo -e "${GREEN}✓${NC} Docker daemon is running"
else
    echo -e "${RED}✗${NC} Docker daemon is not running"
    echo "Please start Docker Desktop and try again"
    exit 1
fi
echo ""

# Check running containers
echo "4. Checking BizSocials containers..."
CONTAINERS=$(docker compose ps --format json 2>/dev/null | jq -r '.Name' 2>/dev/null | wc -l)
if [ "$CONTAINERS" -gt 0 ]; then
    echo -e "${GREEN}✓${NC} Found $CONTAINERS running containers"
    docker compose ps
else
    echo -e "${YELLOW}⚠${NC} No containers running"
    echo "Run 'make up' to start services"
fi
echo ""

# Check service health
echo "5. Checking service health..."
SERVICES=("mysql" "redis" "minio" "meilisearch")
for service in "${SERVICES[@]}"; do
    HEALTH=$(docker compose ps --format json 2>/dev/null | jq -r "select(.Service==\"$service\") | .Health" 2>/dev/null)
    if [ "$HEALTH" == "healthy" ]; then
        echo -e "${GREEN}✓${NC} $service is healthy"
    elif [ -n "$HEALTH" ]; then
        echo -e "${YELLOW}⚠${NC} $service status: $HEALTH"
    else
        echo -e "${RED}✗${NC} $service not found"
    fi
done
echo ""

# Check API endpoint
echo "6. Checking API endpoint..."
if curl -s http://localhost:8080/api &> /dev/null; then
    echo -e "${GREEN}✓${NC} API is responding at http://localhost:8080"
else
    echo -e "${YELLOW}⚠${NC} API not responding (this is normal if containers just started)"
fi
echo ""

# Check .env file
echo "7. Checking environment configuration..."
if [ -f "backend/.env" ]; then
    echo -e "${GREEN}✓${NC} backend/.env file exists"
else
    echo -e "${YELLOW}⚠${NC} backend/.env file not found"
    echo "Run 'make setup-env' to create it"
fi
echo ""

# Check test files
echo "8. Checking test files..."
TEST_COUNT=$(find backend/tests -name "*.php" -type f 2>/dev/null | wc -l)
if [ "$TEST_COUNT" -gt 0 ]; then
    echo -e "${GREEN}✓${NC} Found $TEST_COUNT test files"
else
    echo -e "${RED}✗${NC} No test files found"
fi
echo ""

# Summary
echo "================================"
echo "📊 Summary"
echo "================================"
echo ""

if docker compose ps --format json 2>/dev/null | jq -r '.Health' 2>/dev/null | grep -q "healthy"; then
    echo -e "${GREEN}✓ Your Docker environment is properly set up!${NC}"
    echo ""
    echo "Quick commands:"
    echo "  make test-unit      # Run unit tests"
    echo "  make logs           # View logs"
    echo "  make shell          # Enter container"
    echo "  make help           # See all commands"
    echo ""
    echo "Access your services:"
    echo "  API:         http://localhost:8080"
    echo "  MailHog:     http://localhost:8025"
    echo "  MinIO:       http://localhost:9001"
    echo "  Meilisearch: http://localhost:7700"
else
    echo -e "${YELLOW}⚠ Some services may need attention${NC}"
    echo ""
    echo "Try these commands:"
    echo "  make down           # Stop services"
    echo "  make up             # Start services"
    echo "  make logs           # Check logs"
fi
echo ""
