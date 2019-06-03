if [[ ! "$PWD" =~ scripts ]]; then
  echo "Moving to scripts"
  cd scripts
fi
mkdir -p ../.git/hooks
cp pre-commit ../.git/hooks/
echo "Pre-commit hook installed."
