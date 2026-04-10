import { useState } from "react";

type SaveStatus = "idle" | "success" | "error";

export function useCreateTransition(sceneId: string) {
  const [status, setStatus] = useState<SaveStatus>("idle");
  const [error, setError] = useState<string | null>(null);

  const createTransition = async (
    scene_after_id: string,
    label_forward: string | null,
  ) => {
    try {
      const res = await fetch(`http://localhost:8080/transitions`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          scene_before_id: sceneId,
          scene_after_id,
          label_forward,
          transition_order: 1,
        }),
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
