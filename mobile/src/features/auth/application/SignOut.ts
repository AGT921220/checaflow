import type { SessionStore } from "../domain/SessionStore.ts";

export class SignOut {
    constructor(private readonly sessions: SessionStore) {}

    async handle(): Promise<void> {
        await this.sessions.clear();
    }
}
