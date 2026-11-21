export type UserRole = 'super_admin' | 'company_admin' | 'hr' | 'employee';

export type Session = {
    role: UserRole;
    nickname: string | null;
    email: string | null;
};

export type SignInInput = {
    nickname: string;
    password: string;
    remember?: boolean;
};

export function session(input: Session): Session {
    if (input.nickname === null && input.email === null) {
        throw new Error('La sesión necesita un alias o un correo.');
    }

    return {
        role: input.role,
        nickname: input.nickname,
        email: input.email,
    };
}
