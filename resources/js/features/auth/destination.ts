import { type UserRole } from './types';

const DESTINATIONS: Record<UserRole, string> = {
    super_admin: '/attendance',
    company_admin: '/attendance',
    hr: '/attendance',
    employee: '/punch',
};

function isUserRole(role: string): role is UserRole {
    return Object.hasOwn(DESTINATIONS, role);
}

export function destinationFor(role: string): string {
    if (!isUserRole(role)) {
        throw new Error('Ese rol no tiene un destino.');
    }

    return DESTINATIONS[role];
}
