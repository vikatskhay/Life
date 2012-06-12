/*
 *  PURPOSE:
 *      Init database structure.
 *
 *  AUTHOR(S):
 *      Victoria Tskhay <wmayak@mail.ru>
 */

BEGIN;

CREATE TABLE life (
	id			serial PRIMARY KEY,
	status      integer NOT NULL,
	"timestamp" timestamp with time zone NOT NULL DEFAULT now()
);
ALTER TABLE life OWNER TO john_conway;

CREATE TABLE generation (
	id		  serial PRIMARY KEY,
	life_id   integer NOT NULL,               -- I don't really like foreign keys.
	iteration integer NOT NULL,               -- Iteration number of the generation.
	tweaked   boolean NOT NULL DEFAULT FALSE, -- Whether this generation has been tweaked by user or not.
	"rows"    integer NOT NULL,
	cols      integer NOT NULL,
	bitmap    varchar(1048576) NOT NULL,      -- 2-dimensional array of "0" and "1", stringified and compressed.
                                              -- The type "text" could be used here, but we don't really want this
                                              -- string to get more than that (1048576 = 1024 * 1024).
                                              -- If something like this happens, there must be an exception.

    UNIQUE (life_id, iteration)
);
ALTER TABLE generation OWNER TO john_conway;

COMMIT;