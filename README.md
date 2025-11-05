CSC540 Team Setup Instructions
1️ Clone the Repository into XAMPP htdocs

- Open VS Code Terminal.

- Navigate to your XAMPP htdocs folder:   cd /Applications/XAMPP/htdocs

- Clone the repo: git clone https://github.com/Maleeha2001/CSC540.git

- Go into the repo folder: cd CSC540

2️ Create Your Personal Branch

- Each teammate should work in their own branch to avoid conflicts:

- Xavier: git checkout -b xavier
- Jada: git checkout -b jada

- This creates a new branch and switches you to it automatically.

3️ Sync with main Before Working

- Before starting new work, make sure your branch is up-to-date:

- git checkout main
- git pull origin main
- git checkout your branch
  - Replace your branch with your branch (xavier or jada).
- git merge main

4️ Make Changes and Push

- After editing files or adding new ones:

  - git add .
  - git commit -m "Brief description of changes"
  - git push origin <your-branch-name>


- Example:

- git push origin xavier
- git push origin jada

5️ Create a Pull Request (PR) on GitHub

- Go to the CSC540 repo on GitHub.

- Click “Compare & pull request” for your branch.

- Add a short description of your changes.

- Click “Create pull request”.

- PRs will be reviewed before merging into main.

6️ Guidelines

- Never push directly to main.

- Always pull latest updates before starting work.

- Write clear and concise commit messages.

- Push small commits often to avoid merge conflicts.
