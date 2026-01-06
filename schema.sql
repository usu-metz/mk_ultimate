-- Schéma SQL minimal pour le MVP

CREATE TABLE users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  display_name VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE games (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'waiting'
    CHECK (status IN ('waiting', 'in_progress', 'finished')),
  board_size INT NOT NULL DEFAULT 30,
  laps_to_win INT NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE game_players (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  game_id BIGINT UNSIGNED NOT NULL REFERENCES games(id) ON DELETE CASCADE,
  user_id BIGINT UNSIGNED NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (game_id, user_id)
);

CREATE TABLE turns (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  game_id BIGINT UNSIGNED NOT NULL REFERENCES games(id) ON DELETE CASCADE,
  user_id BIGINT UNSIGNED NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  turn_number INT NOT NULL,
  dice_roll INT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (game_id, turn_number)
);

CREATE TABLE player_positions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  game_id BIGINT UNSIGNED NOT NULL REFERENCES games(id) ON DELETE CASCADE,
  user_id BIGINT UNSIGNED NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  position_index INT NOT NULL,
  lap_count INT NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE (game_id, user_id)
);

CREATE TABLE items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(64) NOT NULL UNIQUE,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE game_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  game_id BIGINT UNSIGNED NOT NULL REFERENCES games(id) ON DELETE CASCADE,
  user_id BIGINT UNSIGNED REFERENCES users(id) ON DELETE SET NULL,
  turn_id BIGINT UNSIGNED REFERENCES turns(id) ON DELETE SET NULL,
  event_type VARCHAR(255) NOT NULL,
  payload JSON NOT NULL DEFAULT (JSON_OBJECT()),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE admin_actions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  game_id BIGINT UNSIGNED REFERENCES games(id) ON DELETE SET NULL,
  user_id BIGINT UNSIGNED REFERENCES users(id) ON DELETE SET NULL,
  action_type VARCHAR(255) NOT NULL,
  reason TEXT NOT NULL,
  before_state JSON,
  after_state JSON,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_game_players_game_id ON game_players(game_id);
CREATE INDEX idx_game_players_user_id ON game_players(user_id);
CREATE INDEX idx_turns_game_id ON turns(game_id);
CREATE INDEX idx_turns_user_id ON turns(user_id);
CREATE INDEX idx_turns_game_turn_number ON turns(game_id, turn_number);
CREATE INDEX idx_player_positions_game_id ON player_positions(game_id);
CREATE INDEX idx_player_positions_user_id ON player_positions(user_id);
CREATE INDEX idx_game_logs_game_id ON game_logs(game_id);
CREATE INDEX idx_game_logs_turn_id ON game_logs(turn_id);
CREATE INDEX idx_admin_actions_game_id ON admin_actions(game_id);
