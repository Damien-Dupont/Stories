import { useState } from "react";

type SaveStatus = "idle" | "success" | "error";

export function useCreateTransition(sceneId: string) {
  const [status, setStatus] = useState<SaveStatus>("idle");
  const [error, setError] = useState<string | null>(null);

  const createTransition = async (
    forwardScene: string,
    forwardLabel: string | null,
  ) => {
    try {
      const res = await fetch(`http://localhost:8080/transitions/${sceneId}`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ forwardScene, forwardLabel }),
      });
      if (!res.ok) throw new Error("Erreur API");
      setStatus("success");
    } catch {
      setStatus("error");
      setError("Erreur lors de la création de transition");
    }
  };
  return {
    createTransition,
    error,
    status,
  };
}
// order des deux scènes // order general // label
