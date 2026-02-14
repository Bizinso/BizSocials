#!/bin/bash

# BizSocials Quick Start Script
# This script sets up everything you need to run BizSocials

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo ""
echo -e "${CYAN}╔════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║   BizSocials Quick Start Setup        ║${NC}"
echo -e "${CYAN}╔════════════════════════════════════════╗${NC}"
echo ""

# Check if Docker is running
echo -e "${YELLOW}Checking Docker...${NC}"
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}❌ Docker is not running!${NC}"
    echo -e "${YELLOW}Please start Docker Desktop and try again.${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Docker is running${NC}"
echo ""

# Check if .env exists
echo -e "${YELLOW}Checking environment file...${NC}"
if [ ! -f backend/.env ]; then
    echo -e "${CYAN}Creating .env file from .env.example...${NC}"
    cp backend/.env.example backend/.env
    echo -e "${GREEN}✅ .env file created${NC}"
else
    echo -e "${GREEN}✅ .env file already exists${NC}"
fi
echo ""

# Run make setup
echo -e "${CYAN}Running full setup (this may take 3-5 minutes)...${NC}"
echo ""
make setup

echo ""
echo -e "${GREEN}╔════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║   Setup Complete! 🎉                   ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════╝${NC}"
echo ""

echo -e "${CYAN}Quick Test:${NC}"
echo -e "  Run: ${YELLOW}make test-unit${NC}"
echo ""

echo -e "${CYAN}View Logs:${NC}"
echo -e "  Run: ${YELLOW}make logs${NC}"
echo ""

echo -e "${CYAN}Enter Container:${NC}"
echo -e "  Run: ${YELLOW}make shell${NC}"
echo ""

echo -e "${CYAN}Stop Services:${NC}"
echo -e "  Run: ${YELLOW}make down${NC}"
echo ""

echo -e "${GREEN}Happy coding! 🚀${NC}"
echo ""
